<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Models\Task;
use A2A\Models\TaskState;
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

        // Use shared storage for both managers
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
            // Enhanced JSON-RPC validation for A2A compliance
            if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
                return $jsonRpc->createError($request['id'] ?? null, 'Invalid JSON-RPC version', A2AErrorCodes::INVALID_REQUEST);
            }

            if (!isset($request['method'])) {
                return $jsonRpc->createError($request['id'] ?? null, 'Missing method', A2AErrorCodes::INVALID_REQUEST);
            }

            // Validate id type (must be string, number, or null)
            $id = $request['id'] ?? null;
            if ($id !== null && !is_string($id) && !is_numeric($id)) {
                return $jsonRpc->createError(null, 'Invalid id type', A2AErrorCodes::INVALID_REQUEST);
            }

            // Validate params (must be object or array if present)
            $params = $request['params'] ?? [];
            if (!is_array($params)) {
                return $jsonRpc->createError($id, 'Invalid params type', A2AErrorCodes::INVALID_PARAMS);
            }

            $parsedRequest = [
                'method' => $request['method'],
                'params' => $params,
                'id' => $id
            ];

            switch ($parsedRequest['method']) {
                case 'message/send':
                case 'send_message':
                    return $this->handleMessage($parsedRequest);

                case 'tasks/send':
                    return $this->handleTasksSend($parsedRequest);

                case 'message/stream':
                    $this->handleStreamMessage($parsedRequest);
                    return [];

                case 'get_agent_card':
                    return $jsonRpc->createResponse(
                        $parsedRequest['id'],
                        $this->agentCard->toArray()
                    );

                case 'ping':
                    return $jsonRpc->createResponse(
                        $parsedRequest['id'],
                        ['status' => 'pong', 'timestamp' => time()]
                    );

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
            $this->logger->error('Request handling failed', [
                'error' => $e->getMessage(),
                'request' => $request
            ]);
            return $jsonRpc->createError(
                $request['id'] ?? null,
                'Internal error: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }

    private function handleMessage(array $parsedRequest): array
    {
        $params = $parsedRequest['params'];

        // Validate message parameter
        if (!isset($params['message'])) {
            $jsonRpc = new JsonRpc();
            return $jsonRpc->createError($parsedRequest['id'], 'Missing message parameter', A2AErrorCodes::INVALID_PARAMS);
        }

        $messageData = $params['message'];

        // Validate message structure
        if (!is_array($messageData) || !isset($messageData['kind']) || $messageData['kind'] !== 'message') {
            $jsonRpc = new JsonRpc();
            return $jsonRpc->createError($parsedRequest['id'], 'Invalid message format', A2AErrorCodes::INVALID_PARAMS);
        }

        // Check for missing required fields
        if (!isset($messageData['messageId']) || !isset($messageData['role']) || !isset($messageData['parts'])) {
            $jsonRpc = new JsonRpc();
            return $jsonRpc->createError($parsedRequest['id'], 'Invalid message: missing required fields', A2AErrorCodes::INVALID_PARAMS);
        }

        // Validate parts array is not empty
        if (!is_array($messageData['parts']) || empty($messageData['parts'])) {
            $jsonRpc = new JsonRpc();
            return $jsonRpc->createError($parsedRequest['id'], 'Invalid message: parts array must not be empty', A2AErrorCodes::INVALID_PARAMS);
        }

        try {
            $message = Message::fromArray($messageData);
        } catch (\Exception $e) {
            $jsonRpc = new JsonRpc();
            return $jsonRpc->createError($parsedRequest['id'], 'Invalid message format: ' . $e->getMessage(), A2AErrorCodes::INVALID_PARAMS);
        }

        $fromAgent = $params['from'] ?? 'unknown';

        $this->logger->info('Message received', [
            'from' => $fromAgent,
            'message_id' => $message->getMessageId(),
            'role' => $message->getRole()
        ]);

        // Handle task creation/continuation  
        $taskId = $params['taskId'] ?? $messageData['taskId'] ?? null;
        $task = null;

        if ($taskId !== null) {
            $task = $this->taskManager->getTask($taskId);
            if (!$task) {
                // A2A Specification §5.1 - Task Not Found Error Handling
                // When attempting to continue a non-existent task, MUST return TaskNotFoundError
                $jsonRpc = new JsonRpc();
                return $jsonRpc->createError($parsedRequest['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
            }
        } else {
            // No task ID provided - create new task with auto-generated ID
            $task = $this->taskManager->createTask('Message task');
        }

        // Set the task ID on the message so handlers can access it
        $message->setTaskId($task->getId());

        // Process message through handlers
        foreach ($this->messageHandlers as $handler) {
            try {
                $handler($message, $fromAgent);
            } catch (\Exception $e) {
                $this->logger->error('Message handler failed', [
                    'error' => $e->getMessage(),
                    'message_id' => $message->getMessageId()
                ]);
            }
        }

        // Return format based on configuration
        $jsonRpc = new JsonRpc();
        if ($this->useTaskObjectResponses) {
            // A2A Protocol compliance mode - return full Task object
            return $jsonRpc->createResponse($parsedRequest['id'], $task->toArray());
        } else {
            // Library compatibility mode - return simple status
            return $jsonRpc->createResponse(
                $parsedRequest['id'],
                [
                    'status' => 'received',
                    'message_id' => $message->getMessageId()
                ]
            );
        }
    }

    private function handleStreamMessage(array $parsedRequest): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        echo "data: " . json_encode([
            'jsonrpc' => '2.0',
            'id' => $parsedRequest['id'],
            'result' => ['status' => 'streaming_ready']
        ]) . "\n\n";
        flush();
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

        $task = $this->taskManager->getTask($taskId);

        if (!$task) {
            return $jsonRpc->createError($parsedRequest['id'], 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        // Use the new method that handles history length
        $taskArray = $task->toArrayWithHistory($historyLength);

        return $jsonRpc->createResponse($parsedRequest['id'], $taskArray);
    }

    private function handleTasksCancel(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError(
                $request['id'],
                'Task ID is required',
                A2AErrorCodes::INVALID_REQUEST
            );
        }

        $result = $this->taskManager->cancelTask($taskId);

        if (isset($result['error'])) {
            return $jsonRpc->createError(
                $request['id'],
                $result['error']['message'],
                $result['error']['code']
            );
        }

        return $jsonRpc->createResponse($request['id'], $result['result']);
    }

    private function handleTasksSend(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $task = $params['task'] ?? null;

        if (!$task) {
            return $jsonRpc->createError(
                $request['id'],
                'Missing task parameter',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        if (!is_array($task)) {
            return $jsonRpc->createError(
                $request['id'],
                'Invalid task format',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        $taskId = $task['id'] ?? null;
        $taskKind = $task['kind'] ?? null;
        $description = $task['description'] ?? '';
        $context = $task['context'] ?? [];

        if (!$taskId) {
            return $jsonRpc->createError(
                $request['id'],
                'Task ID is required',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        if ($taskKind !== 'task') {
            return $jsonRpc->createError(
                $request['id'],
                'Invalid task format',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        if (empty($description)) {
            return $jsonRpc->createError(
                $request['id'],
                'Message content cannot be empty',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        try {
            // Check if task already exists, if not create it
            $existingTask = $this->taskManager->getTask($taskId);
            if (!$existingTask) {
                // Create new task with the provided taskId
                $newTask = $this->taskManager->createTask(
                    $description,
                    $context,
                    $taskId
                );
                $task = $newTask;
            } else {
                $task = $existingTask;
            }

            // Return response based on compliance mode
            if ($this->useTaskObjectResponses) {
                // In compliance mode, return full task object
                return $jsonRpc->createResponse($request['id'], $task->toArray());
            } else {
                // Standard mode, return simple status response
                return $jsonRpc->createResponse($request['id'], [
                    'status' => 'received',
                    'task_id' => $taskId
                ]);
            }
        } catch (\Exception $e) {
            return $jsonRpc->createError(
                $request['id'],
                'Failed to send task: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }

    private function handleTasksResubscribe(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError(
                $request['id'],
                'Task ID is required',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        try {
            // Check if task exists
            $task = $this->taskManager->getTask($taskId);
            if (!$task) {
                return $jsonRpc->createError(
                    $request['id'],
                    'Task not found',
                    A2AErrorCodes::TASK_NOT_FOUND
                );
            }

            // For resubscription, we acknowledge and set up for future updates
            $this->logger->info('Task resubscription requested', ['taskId' => $taskId]);

            return $jsonRpc->createResponse($request['id'], [
                'status' => 'subscribed',
                'taskId' => $taskId
            ]);
        } catch (\Exception $e) {
            return $jsonRpc->createError(
                $request['id'],
                'Failed to resubscribe to task: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }

    private function handlePushNotificationConfigSet(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? null;
        $config = $params['config'] ?? $params['pushNotificationConfig'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError(
                $request['id'],
                'Task ID is required',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        if (!$config || !is_array($config)) {
            return $jsonRpc->createError(
                $request['id'],
                'Valid config object is required',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        try {
            // Validate that task exists
            if (!$this->taskManager->taskExists($taskId)) {
                return $jsonRpc->createError(
                    $request['id'],
                    'Task not found',
                    A2AErrorCodes::TASK_NOT_FOUND
                );
            }

            // Create PushNotificationConfig from array
            $pushConfig = \A2A\Models\PushNotificationConfig::fromArray($config);

            // Set the configuration
            $success = $this->pushNotificationManager->setConfig($taskId, $pushConfig);

            if ($success) {
                return $jsonRpc->createResponse($request['id'], [
                    'pushNotificationConfig' => $pushConfig->toArray()
                ]);
            } else {
                return $jsonRpc->createError(
                    $request['id'],
                    'Failed to set push notification config',
                    A2AErrorCodes::INTERNAL_ERROR
                );
            }
        } catch (\Exception $e) {
            return $jsonRpc->createError(
                $request['id'],
                'Failed to set push notification config: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }

    private function handlePushNotificationConfigGet(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError(
                $request['id'],
                'Task ID is required',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        try {
            $config = $this->pushNotificationManager->getConfig($taskId);

            if ($config) {
                // Return config wrapped in pushNotificationConfig for A2AClient compatibility
                return $jsonRpc->createResponse($request['id'], [
                    'pushNotificationConfig' => $config->toArray()
                ]);
            } else {
                return $jsonRpc->createError(
                    $request['id'],
                    'Push notification config not found for task',
                    A2AErrorCodes::TASK_NOT_FOUND
                );
            }
        } catch (\Exception $e) {
            return $jsonRpc->createError(
                $request['id'],
                'Failed to get push notification config: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }

    private function handlePushNotificationConfigList(array $request): array
    {
        $jsonRpc = new JsonRpc();

        try {
            $configs = $this->pushNotificationManager->listConfigs();
            return $jsonRpc->createResponse($request['id'], $configs);
        } catch (\Exception $e) {
            return $jsonRpc->createError(
                $request['id'],
                'Failed to list push notification configs: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }

    private function handlePushNotificationConfigDelete(array $request): array
    {
        $jsonRpc = new JsonRpc();
        $params = $request['params'] ?? [];
        $taskId = $params['taskId'] ?? $params['id'] ?? null;

        if (!$taskId) {
            return $jsonRpc->createError(
                $request['id'],
                'Task ID is required',
                A2AErrorCodes::INVALID_PARAMS
            );
        }

        try {
            $success = $this->pushNotificationManager->deleteConfig($taskId);

            if ($success) {
                return $jsonRpc->createResponse($request['id'], [
                    'status' => 'deleted',
                    'taskId' => $taskId
                ]);
            } else {
                return $jsonRpc->createError(
                    $request['id'],
                    'Push notification config not found for task',
                    A2AErrorCodes::TASK_NOT_FOUND
                );
            }
        } catch (\Exception $e) {
            return $jsonRpc->createError(
                $request['id'],
                'Failed to delete push notification config: ' . $e->getMessage(),
                A2AErrorCodes::INTERNAL_ERROR
            );
        }
    }
}
