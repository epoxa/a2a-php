<?php

declare(strict_types=1);

namespace A2A\Tests;

use A2A\Models\TextPart;
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
use A2A\TaskManager;

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
            ['text/plain'],
            ['application/json'],
            [$skill]
        );
        
        $server = new A2AServer($serverCard);
        $server->addMessageHandler(
            function (Message $message, $fromAgent) {
                $textContent = '';
                foreach ($message->getParts() as $part) {
                    if ($part instanceof TextPart) {
                        $textContent = $part->getText();
                        break;
                    }
                }
                return ['status' => 'processed', 'echo' => $textContent];
            }
        );

        // Setup client
        $clientCard = new AgentCard(
            'Test Client',
            'Client description',
            'https://example.com/client',
            '1.0.0',
            $capabilities,
            ['text/plain'],
            ['application/json'],
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
            ['text/plain'],
            ['application/json'],
            [$skill]
        );
        
        $taskManager = new TaskManager();
        $protocol = new A2AProtocol($agentCard, null, null, $taskManager);

        // Create task through protocol
        $task = $protocol->createTask('Integration test task', ['test' => true]);
        $this->assertNotEmpty($task->getId());

        // Store task in manager
        $taskManager->updateTask($task);
        
        // Retrieve stored task
        $retrievedTask = $taskManager->getTask($task->getId());
        $this->assertNotNull($retrievedTask);
        $this->assertEquals($task->getId(), $retrievedTask->getId());
    }
}