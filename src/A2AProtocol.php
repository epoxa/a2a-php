<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\AgentCard;
use A2A\Models\Task;
use A2A\Models\Message;
use A2A\Models\Part;
use A2A\Utils\JsonRpc;
use A2A\Utils\HttpClient;
use A2A\Exceptions\A2AException;
use A2A\Exceptions\InvalidRequestException;
use A2A\Exceptions\TaskNotFoundException;
use A2A\Interfaces\MessageHandlerInterface;
use Ramsey\Uuid\Uuid;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class A2AProtocol
{
    private HttpClient $httpClient;
    private LoggerInterface $logger;
    private string $agentId;
    private AgentCard $agentCard;
    private array $messageHandlers = [];

    public function __construct(
        AgentCard $agentCard,
        ?HttpClient $httpClient = null,
        ?LoggerInterface $logger = null
    ) {
        $this->agentCard = $agentCard;
        $this->agentId = $agentCard->getId();
        $this->httpClient = $httpClient ?? new HttpClient();
        $this->logger = $logger ?? new NullLogger();
    }

    public function getAgentCard(): AgentCard
    {
        return $this->agentCard;
    }

    public function createTask(string $description, array $context = []): Task
    {
        $taskId = Uuid::uuid4()->toString();
        $task = new Task($taskId, $description, $context);

        $this->logger->info('Task created', [
            'task_id' => $taskId,
            'description' => $description
        ]);

        return $task;
    }

    public function sendMessage(string $recipientUrl, Message $message): array
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('send_message', [
            'from' => $this->agentId,
            'message' => $message->toArray()
        ]);

        try {
            $response = $this->httpClient->post($recipientUrl, $request);
            $this->logger->info('Message sent successfully', [
                'recipient' => $recipientUrl,
                'message_id' => $message->getId()
            ]);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send message', [
                'recipient' => $recipientUrl,
                'error' => $e->getMessage()
            ]);
            throw new A2AException('Failed to send message: ' . $e->getMessage());
        }
    }

    public function handleRequest(array $request): array
    {
        $jsonRpc = new JsonRpc();

        try {
            $parsedRequest = $jsonRpc->parseRequest($request);

            switch ($parsedRequest['method']) {
                case 'send_message':
                    return $this->handleMessage($parsedRequest['params'], $parsedRequest['id']);
                case 'get_agent_card':
                    return $jsonRpc->createResponse($parsedRequest['id'], $this->agentCard->toArray());
                case 'ping':
                    return $jsonRpc->createResponse($parsedRequest['id'], ['status' => 'pong']);
                default:
                    throw new InvalidRequestException('Unknown method: ' . $parsedRequest['method']);
            }
        } catch (\Exception $e) {
            $this->logger->error('Request handling failed', ['error' => $e->getMessage()]);
            return $jsonRpc->createError($request['id'] ?? null, $e->getMessage());
        }
    }

    private function handleMessage(array $params, $requestId): array
    {
        $message = Message::fromArray($params['message']);
        $fromAgent = $params['from'] ?? 'unknown';

        $this->logger->info('Message received', [
            'from' => $fromAgent,
            'message_id' => $message->getId(),
            'content_type' => $message->getType()
        ]);

        $result = $this->processMessage($message, $fromAgent);

        $jsonRpc = new JsonRpc();
        return $jsonRpc->createResponse($requestId, $result);
    }

    public function addMessageHandler(MessageHandlerInterface $handler): void
    {
        $this->messageHandlers[] = $handler;
    }

    protected function processMessage(Message $message, string $fromAgent): array
    {
        foreach ($this->messageHandlers as $handler) {
            if ($handler->canHandle($message)) {
                try {
                    return $handler->handle($message, $fromAgent);
                } catch (\Exception $e) {
                    $this->logger->error('Message handler failed', [
                        'handler' => get_class($handler),
                        'error' => $e->getMessage(),
                        'message_id' => $message->getId()
                    ]);
                }
            }
        }

        // Default response if no handler processes the message
        return [
            'status' => 'received',
            'message_id' => $message->getId(),
            'timestamp' => time()
        ];
    }
}
