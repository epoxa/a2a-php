<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\AgentCard;
use A2A\Models\Message;
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
            'message' => $message->toArray()
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
}
