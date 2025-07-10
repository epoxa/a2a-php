<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Models\Task;
use A2A\Models\PushNotificationConfig;
use A2A\Utils\HttpClient;
use A2A\Utils\JsonRpc;
use A2A\Exceptions\A2AException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class A2AClient
{
    private HttpClient $httpClient;
    private LoggerInterface $logger;
    private AgentCard $agentCard;
    public function __construct(
        AgentCard $agentCard,
        ?HttpClient $httpClient = null,
        ?LoggerInterface $logger = null
    )
    {
        $this->agentCard = $agentCard;
        $this->httpClient = $httpClient ?? new HttpClient();
        $this->logger = $logger ?? new NullLogger();
    }

    public function sendMessage(string $agentUrl, Message $message): array
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('send_message', [
            'from' => $this->agentCard->getId(),
            'message' => $message->toProtocolArray()
        ], 1);

        try {
            $response = $this->httpClient->post($agentUrl, $request);
            $this->logger->info('Message sent', [
                'to' => $agentUrl,
                'message_id' => $message->getId()
            ]);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send message', [
                'to' => $agentUrl,
                'error' => $e->getMessage()
            ]);
            throw new A2AException('Failed to send message: ' . $e->getMessage());
        }
    }

    public function getAgentCard(string $agentUrl): AgentCard
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('get_agent_card', [], 1);

        try {
            $response = $this->httpClient->post($agentUrl, $request);
            return AgentCard::fromArray($response['result']);
        } catch (\Exception $e) {
            throw new A2AException('Failed to get agent card: ' . $e->getMessage());
        }
    }

    public function ping(string $agentUrl): bool
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('ping', [], 1);

        try {
            $response = $this->httpClient->post($agentUrl, $request);
            return isset($response['result']['status']) && $response['result']['status'] === 'pong';
        } catch (\Exception $e) {
            $this->logger->warning('Ping failed', [
                'to' => $agentUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getTask(string $taskId, ?int $historyLength = null): ?Task
    {
        $jsonRpc = new JsonRpc();
        $params = ['id' => $taskId];
        if ($historyLength !== null) {
            $params['historyLength'] = $historyLength;
        }
        $request = $jsonRpc->createRequest('tasks/get', $params, 1);

        try {
            $response = $this->httpClient->post('', $request);
            if (isset($response['result'])) {
                return Task::fromArray($response['result']);
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get task', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function cancelTask(string $taskId): bool
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('tasks/cancel', ['id' => $taskId], 1);

        try {
            $response = $this->httpClient->post('', $request);
            return isset($response['result']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel task', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function setPushNotificationConfig(string $taskId, PushNotificationConfig $config): bool
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('tasks/pushNotificationConfig/set', [
            'taskId' => $taskId,
            'pushNotificationConfig' => $config->toArray()
        ], 1);

        try {
            $response = $this->httpClient->post('', $request);
            return isset($response['result']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to set push notification config', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
