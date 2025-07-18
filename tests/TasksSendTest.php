<?php

declare(strict_types=1);

namespace A2A\Tests;

use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\TaskManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TasksSendTest extends TestCase
{
    private A2AServer $server;
    private AgentCard $agentCard;
    private LoggerInterface $logger;
    private TaskManager $taskManager;

    protected function setUp(): void
    {
        $capabilities = new AgentCapabilities(true, true, true);
        $this->agentCard = new AgentCard(
            'Test Agent',
            'A test agent for unit testing',
            'http://localhost:8080',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [],
            '0.2.5'
        );

        $this->logger = new NullLogger();
        $this->taskManager = new TaskManager();
        $this->server = new A2AServer($this->agentCard, $this->logger, $this->taskManager);
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