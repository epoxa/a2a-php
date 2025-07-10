<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AClient;
use A2A\A2AServer;
use A2A\A2AProtocol;
use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Utils\HttpClient;
use A2A\Handlers\EchoMessageHandler;

class IntegrationTest extends TestCase
{
    public function testClientServerCommunication(): void
    {
        // Setup server
        $serverCard = new AgentCard('server-001', 'Test Server');
        $server = new A2AServer($serverCard);
        $server->addMessageHandler(function($message, $fromAgent) {
            return ['status' => 'processed', 'echo' => $message->getContent()];
        });

        // Setup client
        $clientCard = new AgentCard('client-001', 'Test Client');
        $mockHttpClient = $this->createMock(HttpClient::class);
        $client = new A2AClient($clientCard, $mockHttpClient);

        // Test message flow
        $message = new Message('Hello Server', 'text');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'send_message',
            'params' => [
                'from' => 'client-001',
                'message' => $message->toProtocolArray()
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
        $agentCard = new AgentCard('protocol-001', 'Protocol Agent');
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

    public function testEndToEndMessageHandling(): void
    {
        $serverCard = new AgentCard('server-002', 'E2E Server');
        $protocol = new A2AProtocol($serverCard);
        
        $handlerCalled = false;
        $protocol->addMessageHandler(new class($handlerCalled) implements \A2A\Interfaces\MessageHandlerInterface {
            private $called;
            
            public function __construct(&$called) {
                $this->called = &$called;
            }
            
            public function canHandle(\A2A\Models\Message $message): bool {
                return true;
            }
            
            public function handle(\A2A\Models\Message $message, string $fromAgent): array {
                $this->called = true;
                return ['status' => 'custom_handled'];
            }
        });

        $message = new Message('E2E Test', 'text');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'send_message',
            'params' => [
                'from' => 'client-e2e',
                'message' => $message->toProtocolArray()
            ],
            'id' => 1
        ];

        $response = $protocol->handleRequest($request);
        
        $this->assertTrue($handlerCalled);
        $this->assertEquals('custom_handled', $response['result']['status']);
    }
}