<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\TaskManager;

class A2AServerTest extends TestCase
{
    private A2AServer $server;
    private AgentCard $agentCard;
    private TestLogger $logger;
    private TaskManager $taskManager; // Add TaskManager property

    protected function setUp(): void
    {
        $capabilities = new AgentCapabilities();
        $skill = new AgentSkill('test', 'Test', 'Test skill', ['test']);

        $this->agentCard = new AgentCard(
            'Server Agent',
            'Test server agent',
            'https://example.com/agent',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );

        $this->logger = new TestLogger();
        $this->taskManager = new TaskManager(); // Initialize TaskManager
        $this->server = new A2AServer($this->agentCard, $this->logger, $this->taskManager);
    }

    public function testHandleGetAgentCardRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'get_agent_card',
            'id' => 1
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(1, $response['id']);
        $this->assertEquals('Server Agent', $response['result']['name']);
    }

    public function testHandlePingRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'ping',
            'id' => 2
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(2, $response['id']);
        $this->assertEquals('pong', $response['result']['status']);
        $this->assertArrayHasKey('timestamp', $response['result']);
    }

    public function testHandleMessageRequest(): void
    {
        $messageHandled = false;

        $this->server->addMessageHandler(
            function ($message, $fromAgent) use (&$messageHandled) {
                $messageHandled = true;
                $this->assertEquals('Hello Server', $message->getTextContent());
                $this->assertEquals('client-agent', $fromAgent);
            }
        );

        $message = Message::createUserMessage('Hello Server');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'message/send',
            'params' => [
                'from' => 'client-agent',
                'message' => $message->toArray()
            ],
            'id' => 3
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(3, $response['id']);
        $this->assertEquals('received', $response['result']['status']);
        $this->assertEquals($message->getMessageId(), $response['result']['message_id']);
        $this->assertTrue($messageHandled);
        $this->assertTrue($this->logger->hasRecordThatContains('info', 'Message received'));
    }

    public function testHandleInvalidRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'unknown_method',
            'id' => 4
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(4, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Unknown method', $response['error']['message']);
    }

    public function testMessageHandlerException(): void
    {
        $this->server->addMessageHandler(
            function ($message, $fromAgent) {
                throw new \Exception('Handler error');
            }
        );

        $message = Message::createUserMessage('Test');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'message/send',
            'params' => [
                'from' => 'client-agent',
                'message' => $message->toArray()
            ],
            'id' => 5
        ];

        $response = $this->server->handleRequest($request);

        // Should still return success despite handler error
        $this->assertEquals('received', $response['result']['status']);
        $this->assertTrue($this->logger->hasRecordThatContains('error', 'Message handler failed'));
    }

    public function testHandleInvalidJsonRpc(): void
    {
        $request = [
            'method' => 'test',
            'id' => 1
            // Missing jsonrpc field
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testHandleEmptyRequest(): void
    {
        $response = $this->server->handleRequest([]);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testMultipleMessageHandlers(): void
    {
        $handler1Called = false;
        $handler2Called = false;

        $this->server->addMessageHandler(
            function ($message, $fromAgent) use (&$handler1Called) {
                $handler1Called = true;
            }
        );

        $this->server->addMessageHandler(
            function ($message, $fromAgent) use (&$handler2Called) {
                $handler2Called = true;
            }
        );

        $message = Message::createUserMessage('Test');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'message/send',
            'params' => [
                'from' => 'client-agent',
                'message' => $message->toArray()
            ],
            'id' => 6
        ];

        $this->server->handleRequest($request);

        $this->assertTrue($handler1Called);
        $this->assertTrue($handler2Called);
    }

    public function testHandleTasksSendRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => [
                    'kind' => 'task',
                    'id' => 'test-task-123',
                    'description' => 'Test task for A2A protocol',
                    'context' => ['key' => 'value']
                ]
            ],
            'id' => 'test-tasks-send'
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('test-tasks-send', $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('received', $response['result']['status']);
        $this->assertEquals('test-task-123', $response['result']['task_id']);
    }

    public function testHandleTasksSendWithInvalidTask(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => [
                    'kind' => 'invalid',  // Invalid kind
                    'id' => 'test-task-123'
                ]
            ],
            'id' => 'test-tasks-send-invalid'
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('test-tasks-send-invalid', $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(-32602, $response['error']['code']);
        $this->assertStringContainsString('Invalid task format', $response['error']['message']);
    }

    public function testHandleTasksSendMissingTaskParameter(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [],  // Missing task parameter
            'id' => 'test-tasks-send-missing'
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('test-tasks-send-missing', $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(-32602, $response['error']['code']);
        $this->assertStringContainsString('Missing task parameter', $response['error']['message']);
    }

    public function testHandleTasksSendInvalidParams(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                // Missing required 'task' parameter
                'metadata' => ['test' => 'value']
            ],
            'id' => 8
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(8, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Missing task parameter', $response['error']['message']);
    }

    public function testHandleTasksSendMissingId(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => [
                    'kind' => 'task',
                    'description' => 'Task without ID',
                    'context' => ['priority' => 'low']
                    // Missing 'id' field
                ]
            ],
            'id' => 9
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(9, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Task ID is required', $response['error']['message']);
    }

    public function testHandleTasksSendEmptyMessage(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => [
                    'kind' => 'task',
                    'id' => 'empty-message-task',
                    'description' => '', // Empty description
                    'context' => ['priority' => 'medium']
                ]
            ],
            'id' => 10
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(10, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Message content cannot be empty', $response['error']['message']);
    }

    public function testHandleTasksSendInvalidTaskStructure(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => 'not-an-array' // Invalid task structure
            ],
            'id' => 11
        ];

        $response = $this->server->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(11, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid task format', $response['error']['message']);
    }

    // Add test for A2A compliance mode with tasks/send
    public function testA2AComplianceModeTasksSend(): void
    {
        // Create server in A2A compliance mode
        $complianceServer = new A2AServer($this->agentCard, $this->logger, $this->taskManager, true);

        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => [
                    'kind' => 'task',
                    'id' => 'compliance-task-123',
                    'description' => 'A2A compliance test task'
                ]
            ],
            'id' => 'test-compliance-tasks-send'
        ];

        $response = $complianceServer->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('test-compliance-tasks-send', $response['id']);
        $this->assertArrayHasKey('result', $response);

        // In compliance mode, should return full task object
        $this->assertArrayHasKey('kind', $response['result']);
        $this->assertEquals('task', $response['result']['kind']);
        $this->assertEquals('compliance-task-123', $response['result']['id']);
    }

    public function testTasksPersistenceBetweenMethods(): void
    {
        // First create a task via tasks/send
        $sendRequest = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/send',
            'params' => [
                'task' => [
                    'kind' => 'task',
                    'id' => 'persistent-task-123',
                    'description' => 'Task for persistence test'
                ]
            ],
            'id' => 'test-send'
        ];

        $this->server->handleRequest($sendRequest);

        // Then retrieve it via tasks/get
        $getRequest = [
            'jsonrpc' => '2.0',
            'method' => 'tasks/get',
            'params' => ['id' => 'persistent-task-123'],
            'id' => 'test-get'
        ];

        $response = $this->server->handleRequest($getRequest);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('test-get', $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('persistent-task-123', $response['result']['id']);
    }
}
