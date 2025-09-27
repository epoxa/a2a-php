<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Models\TaskStatus;
use A2A\TaskManager;
use A2A\PushNotificationManager;
use A2A\Storage\Storage;
use A2A\Utils\JsonRpc;
use A2A\Exceptions\A2AException;
use A2A\Exceptions\A2AErrorCodes;
use A2A\Exceptions\InvalidRequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class A2AServer
{
    private AgentCard $agentCard;
    private LoggerInterface $logger;
    private array $messageHandlers;
    private TaskManager $taskManager;
    private PushNotificationManager $pushNotificationManager;
    private bool $useTaskObjectResponses;

    public function __construct(
        AgentCard $agentCard,
        ?LoggerInterface $logger = null,
        ?TaskManager $taskManager = null,
        bool $useTaskObjectResponses = false,
        ?Storage $storage = null
    ) {
        $this->agentCard = $agentCard;
        $this->logger = $logger ?? new NullLogger();
        $this->messageHandlers = [];

        $sharedStorage = $storage ?? new Storage();
        $this->taskManager = $taskManager ?? new TaskManager($sharedStorage);
        $this->pushNotificationManager = new PushNotificationManager($sharedStorage);
        $this->useTaskObjectResponses = $useTaskObjectResponses;
    }

    public function addMessageHandler(callable $handler): void
    {
        $this->messageHandlers[] = $handler;
    }

    public function handleRequest(array $request): array
    {
        $jsonRpc = new JsonRpc();

        try {
            if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
                return $jsonRpc->createError($request['id'] ?? null, 'Invalid JSON-RPC version', A2AErrorCodes::INVALID_REQUEST);
            }
            if (!isset($request['method'])) {
                return $jsonRpc->createError($request['id'] ?? null, 'Missing method', A2AErrorCodes::INVALID_REQUEST);
            }

            $id = $request['id'] ?? null;
            if ($id !== null && !is_string($id) && !is_numeric($id)) {
                return $jsonRpc->createError(null, 'Invalid id type', A2AErrorCodes::INVALID_REQUEST);
            }

            $params = $request['params'] ?? [];
            if (!is_array($params)) {
                return $jsonRpc->createError($id, 'Invalid params type', A2AErrorCodes::INVALID_PARAMS);
            }

            $parsedRequest = ['method' => $request['method'], 'params' => $params, 'id' => $id];

            switch ($parsedRequest['method']) {
                case 'message/send':
                case 'send_message':
                    return $this->handleMessage($parsedRequest);
                case 'tasks/send':
                    return $this->handleTasksSend($parsedRequest);
                case 'get_agent_card':
                    return $jsonRpc->createResponse($parsedRequest['id'], $this->agentCard->toArray());
                case 'ping':
                    return $jsonRpc->createResponse($parsedRequest['id'], ['status' => 'pong', 'timestamp' => time()]);
                case 'tasks/get':
                    return $this->handleTasksGet($parsedRequest);
                case 'tasks/cancel':
                    return $this->handleTasksCancel($parsedRequest);
                case 'tasks/resubscribe':
                    return $this->handleTasksResubscribe($parsedRequest);
                case 'tasks/pushNotificationConfig/set':
                    return $this->handlePushNotificationConfigSet($parsedRequest);
                case 'tasks/pushNotificationConfig/get':
                    return $this->handlePushNotificationConfigGet($parsedRequest);
                case 'tasks/pushNotificationConfig/list':
                    return $this->handlePushNotificationConfigList($parsedRequest);
                case 'tasks/pushNotificationConfig/delete':
                    return $this->handlePushNotificationConfigDelete($parsedRequest);
                default:
                    return $jsonRpc->createError($parsedRequest['id'], 'Unknown method', A2AErrorCodes::METHOD_NOT_FOUND);
            }
        } catch (\Exception $e) {
            $this->logger->error('Request handling failed', ['error' => $e->getMessage(), 'request' => $request]);
            return $jsonRpc->createError($request['id'] ?? null, 'Internal error: ' . $e->getMessage(), A2AErrorCodes::INTERNAL_ERROR);
        }
    }

    private function handleMessage(array $parsedRequest): array
    {
        $params = $parsedRequest['params'];
        $jsonRpc = new JsonRpc();

        if (!isset($params['message'])) {
            return $jsonRpc->createError($parsedRequest['id'], 'Missing message parameter', A2AErrorCodes::INVALID_PARAMS);
        }

        try {
            $message = Message::fromArray($params['message']);
        } catch (\Exception $e) {
            return $jsonRpc->createError($parsedRequest['id'], 'Invalid message format: ' . $e->getMessage(), A2AErrorCodes::INVALID_PARAMS);
        }

        $fromAgent = $params['from'] ?? 'unknown';
        $this->logger->info('Message received', ['from' => $fromAgent, 'message_id' => $message->getMessageId()]);

        $taskId = $message->getTaskId();
        if ($taskId) {
            $task = $this->taskManager->getTask($taskId);
            if (!$task) {
                return $jsonRpc->createError($parsedRequest['id'], "Task not found: {$taskId}", A2AErrorCodes::TASK_NOT_FOUND);
            }
        } else {
            $task = $this->taskManager->createTask('Message task');
            $message->setTaskId($task->getId());
        }

        $task->addToHistory($message);
        $this->progressTaskState($task, $message);
        $this->taskManager->updateTask($task);

        foreach ($this->messageHandlers as $handler) {
            try {
                $handler($message, $fromAgent);
            } catch (\Exception $e) {
                $this->logger->error('Message handler failed', ['error' => $e->getMessage(), 'message_id' => $message->getMessageId()]);
            }
        }

        if ($this->useTaskObjectResponses) {
            return $jsonRpc->createResponse($parsedRequest['id'], $task->toArray());
        } else {
            return $jsonRpc->createResponse($parsedRequest['id'], ['status' => 'received', 'message_id' => $message->getMessageId(), 'timestamp' => time()]);
        }
    }

    private function handleTasksSend(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskData = $params['task'] ?? null;

        if (!$taskData || !is_array($taskData)) {
            return $jsonRpc->createError($request['id'], 'Missing or invalid task parameter', A2AErrorCodes::INVALID_PARAMS);
        }
        if (empty($taskData['id'])) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_PARAMS);
        }
        if (($taskData['kind'] ?? null) !== 'task') {
            return $jsonRpc->createError($request['id'], 'Invalid task format', A2AErrorCodes::INVALID_PARAMS);
        }
        $description = $taskData['description'] ?? '';
        if (empty($description)) {
            return $jsonRpc->createError($request['id'], 'Task description cannot be empty', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskData['id']);
        if (!$task) {
            $task = $this->taskManager->createTask($description, $taskData['context'] ?? [], $taskData['id']);
        }

        if ($this->useTaskObjectResponses) {
            return $jsonRpc->createResponse($request['id'], $task->toArray());
        } else {
            return $jsonRpc->createResponse($request['id'], ['status' => 'received', 'task_id' => $task->getId()]);
        }
    }

    private function handleTasksGet(array $parsedRequest): array
    {
        $jsonRpc = new JsonRpc();
        $params = $parsedRequest['params'];

        if (!isset($params['id'])) {
            return $jsonRpc->createError($parsedRequest['id'], 'Missing id parameter', A2AErrorCodes::INVALID_PARAMS);
        }

        $taskId = $params['id'];
        $historyLength = $params['historyLength'] ?? null;

        if ($historyLength !== null && (!is_int($historyLength) || $historyLength < 0)) {
            return $jsonRpc->createError($parsedRequest['id'], 'historyLength must be a non-negative integer', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskId);

        if (!$task) {
            return $jsonRpc->createError($parsedRequest['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        $taskArray = $task->toArray();

        if ($historyLength !== null) {
            $history = $task->getHistory($historyLength);
            $taskArray['history'] = array_map(fn (Message $message) => $message->toArray(), $history);
        }

        return $jsonRpc->createResponse($parsedRequest['id'], $taskArray);
    }

    private function handleTasksCancel(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_REQUEST);
        }

        $result = $this->taskManager->cancelTask($taskId);

        if (isset($result['error'])) {
            return $jsonRpc->createError($request['id'], $result['error']['message'], $result['error']['code']);
        }

        return $jsonRpc->createResponse($request['id'], $result['result']);
    }

    private function handleTasksResubscribe(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            return $jsonRpc->createError($request['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        return $jsonRpc->createResponse($request['id'], ['status' => 'subscribed', 'taskId' => $taskId]);
    }

    private function handlePushNotificationConfigSet(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? null;
        $config = $params['config'] ?? $params['pushNotificationConfig'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            return $jsonRpc->createError($request['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        if (!$config || !is_array($config)) {
            return $jsonRpc->createError($request['id'], 'Valid config object is required', A2AErrorCodes::INVALID_PARAMS);
        }

        $pushConfig = \A2A\Models\PushNotificationConfig::fromArray($config);
        $this->pushNotificationManager->setConfig($taskId, $pushConfig);

        return $jsonRpc->createResponse($request['id'], ['pushNotificationConfig' => $pushConfig->toArray(), 'status' => 'configured', 'taskId' => $taskId]);
    }

    private function handlePushNotificationConfigGet(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            return $jsonRpc->createError($request['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        $config = $this->pushNotificationManager->getConfig($taskId);
        if ($config) {
            return $jsonRpc->createResponse($request['id'], ['pushNotificationConfig' => $config->toArray()]);
        } else {
            return $jsonRpc->createError($request['id'], 'Push notification config not found for task', A2AErrorCodes::TASK_NOT_FOUND);
        }
    }

    private function handlePushNotificationConfigList(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            return $jsonRpc->createError($request['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        $configs = $this->pushNotificationManager->listConfigsForTask($taskId);
        $formattedConfigs = array_map(fn ($c) => ['taskId' => $taskId, 'pushNotificationConfig' => $c->toArray()], $configs);
        return $jsonRpc->createResponse($request['id'], $formattedConfigs);
    }

    private function handlePushNotificationConfigDelete(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError($request['id'], 'Task ID is required', A2AErrorCodes::INVALID_PARAMS);
        }

        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            return $jsonRpc->createError($request['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        $this->pushNotificationManager->deleteConfig($taskId);
        return $jsonRpc->createResponse($request['id'], null);
    }

    private function progressTaskState(Task $task, Message $message): void
    {
        $currentState = $task->getStatus()->getState();

        if ($currentState->isTerminal()) {
            return;
        }

        if ($currentState === TaskState::SUBMITTED) {
            $task->setStatus(new TaskStatus(TaskState::WORKING));
            $this->logger->info('Task state progressed to working', ['taskId' => $task->getId()]);
        }
    }
}