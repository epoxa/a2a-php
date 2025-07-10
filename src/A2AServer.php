<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Utils\JsonRpc;
use A2A\Exceptions\A2AException;
use A2A\Exceptions\InvalidRequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class A2AServer
{
    private AgentCard $agentCard;
    private LoggerInterface $logger;
    private array $messageHandlers;

    public function __construct(
        AgentCard $agentCard,
        ?LoggerInterface $logger = null
    ) {
        $this->agentCard = $agentCard;
        $this->logger = $logger ?? new NullLogger();
        $this->messageHandlers = [];
    }

    public function addMessageHandler(callable $handler): void
    {
        $this->messageHandlers[] = $handler;
    }

    public function handleRequest(array $request): array
    {
        $jsonRpc = new JsonRpc();

        try {
            if (!$jsonRpc->isValidRequest($request)) {
                throw new InvalidRequestException('Invalid JSON-RPC request');
            }

            $parsedRequest = $jsonRpc->parseRequest($request);

            switch ($parsedRequest['method']) {
                case 'send_message':
                    return $this->handleMessage($parsedRequest);
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
                default:
                    throw new InvalidRequestException('Unknown method: ' . $parsedRequest['method']);
            }
        } catch (\Exception $e) {
            $this->logger->error('Request handling failed', [
                'error' => $e->getMessage(),
                'request' => $request
            ]);
            return $jsonRpc->createError(
                $request['id'] ?? null,
                $e->getMessage()
            );
        }
    }

    private function handleMessage(array $parsedRequest): array
    {
        $params = $parsedRequest['params'];
        $message = Message::fromArray($params['message']);
        $fromAgent = $params['from'] ?? 'unknown';

        $this->logger->info('Message received', [
            'from' => $fromAgent,
            'message_id' => $message->getId(),
            'content_type' => $message->getType()
        ]);

        // Process message through handlers
        foreach ($this->messageHandlers as $handler) {
            try {
                $handler($message, $fromAgent);
            } catch (\Exception $e) {
                $this->logger->error('Message handler failed', [
                    'error' => $e->getMessage(),
                    'message_id' => $message->getId()
                ]);
            }
        }

        $jsonRpc = new JsonRpc();
        return $jsonRpc->createResponse(
            $parsedRequest['id'],
            [
                'status' => 'received',
                'message_id' => $message->getId(),
                'timestamp' => time()
            ]
        );
    }
}
