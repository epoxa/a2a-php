<?php

declare(strict_types=1);

namespace A2A\Tests;

use A2A\A2AProtocol_v0_3_0;
use A2A\Interfaces\MessageHandlerInterface;
use A2A\Models\TextPart;
use PHPUnit\Framework\TestCase;
use A2A\A2AClient;
use A2A\A2AServer;
use A2A\Models\v0_3_0\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\v0_3_0\Message;
use A2A\Utils\HttpClient;
use A2A\TaskManager;
use A2A\Storage\Storage;

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
        
        $protocol = new A2AProtocol_v0_3_0($serverCard);
        $server = new A2AServer($protocol);

        $messageHandler = new class implements MessageHandlerInterface {
            public function canHandle(Message $message): bool
            {
                return true;
            }

            public function handle(Message $message, string $fromAgent): array
            {
                $textContent = '';
                foreach ($message->getParts() as $part) {
                    if ($part instanceof TextPart) {
                        $textContent = $part->getText();
                        break;
                    }
                }
                return ['status' => 'processed', 'echo' => $textContent];
            }
        };
        $protocol->addMessageHandler($messageHandler);

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
        $this->assertEquals(['status' => 'processed', 'echo' => 'Hello Server'], $response['result']);
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
        
        $taskManager = new TaskManager(new Storage('array'));
        $protocol = new A2AProtocol_v0_3_0($agentCard, null, null, $taskManager);

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