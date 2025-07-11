<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AClient;
use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\Utils\HttpClient;

class EndToEndTest extends TestCase
{
    public function testCompleteAgentScenario(): void
    {
        // Setup agent
        $capabilities = new AgentCapabilities(true, false, true);
        $skill = new AgentSkill('chat', 'Chat', 'Chat capability', ['chat']);
        
        $agentCard = new AgentCard(
            'E2E Agent',
            'End-to-end test agent',
            'https://example.com/agent',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );

        // Setup server
        $server = new A2AServer($agentCard);
        $messageReceived = false;
        
        $server->addMessageHandler(function($message, $fromAgent) use (&$messageReceived) {
            $messageReceived = true;
            $this->assertEquals('Hello Agent', $message->getTextContent());
        });

        // Setup client with mock HTTP
        $httpClient = new class extends HttpClient {
            private A2AServer $server;
            
            public function setServer(A2AServer $server): void {
                $this->server = $server;
            }
            
            public function post(string $url, array $data): array {
                return $this->server->handleRequest($data);
            }
        };
        
        $httpClient->setServer($server);
        $client = new A2AClient($agentCard, $httpClient);

        // Test complete flow
        $message = Message::createUserMessage('Hello Agent');
        $response = $client->sendMessage('http://test', $message);
        
        $this->assertTrue($messageReceived);
        $this->assertEquals('received', $response['result']['status']);
    }
}