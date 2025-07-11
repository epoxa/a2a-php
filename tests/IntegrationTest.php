<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AClient;
use A2A\A2AServer;
use A2A\A2AProtocol;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\Utils\HttpClient;
use A2A\Handlers\EchoMessageHandler;

class IntegrationTest extends TestCase
{
    public function testClientServerCommunication(): void
    {
        // Setup server
        $capabilities = new AgentCapabilities();
        $skill = new AgentSkill('test', 'Test', 'Test skill', ['test']);
        
        $serverCard = new AgentCard(
            'Test Server',
            'Server description',
            'https://example.com/server',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );
        
        $server = new A2AServer($serverCard);
        $server->addMessageHandler(function($message, $fromAgent) {
            return ['status' => 'processed', 'echo' => $message->getTextContent()];
        });

        // Setup client
        $clientCard = new AgentCard(
            'Test Client',
            'Client description',
            'https://example.com/client',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );
        
        $mockHttpClient = $this->createMock(HttpClient::class);
        $client = new A2AClient($clientCard, $mockHttpClient);

        // Test message flow
        $message = Message::createUserMessage('Hello Server');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'message/send',
            'params' => [
                'from' => 'Test Client',
                'message' => $message->toArray()
            ],
            'id' => 1
        ];

        $response = $server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(1, $response['id']);
        $this->assertEquals('received', $response['result']['status']);
    }

    public function testProtocolTaskManagement(): void
    {
        $capabilities = new AgentCapabilities();
        $skill = new AgentSkill('test', 'Test', 'Test skill', ['test']);
        
        $agentCard = new AgentCard(
            'Protocol Agent',
            'Protocol description',
            'https://example.com/protocol',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );
        
        $protocol = new A2AProtocol($agentCard);

        // Create task through protocol
        $task = $protocol->createTask('Integration test task', ['test' => true]);
        $this->assertNotEmpty($task->getId());

        // Store task in manager
        $taskManager = $protocol->getTaskManager();
        $storedTask = $taskManager->createTask('Integration test task', ['test' => true]);
        
        // Retrieve stored task
        $retrievedTask = $taskManager->getTask($storedTask->getId());
        $this->assertNotNull($retrievedTask);
    }
}