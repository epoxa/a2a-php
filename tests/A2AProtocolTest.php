<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AProtocol;
use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Models\TaskState;
use A2A\Utils\HttpClient;

class A2AProtocolTest extends TestCase
{
    private A2AProtocol $protocol;
    private AgentCard $agentCard;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $capabilities = new \A2A\Models\AgentCapabilities();
        $skill = new \A2A\Models\AgentSkill('test', 'Test', 'Test skill', ['test']);

        $this->agentCard = new AgentCard(
            'Test Agent',
            'A test agent for unit testing',
            'https://example.com/agent',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );

        $this->logger = new TestLogger();
        $httpClient = $this->createMock(HttpClient::class);

        $this->protocol = new A2AProtocol(
            $this->agentCard,
            $httpClient,
            $this->logger
        );
    }

    public function testGetAgentCard(): void
    {
        $card = $this->protocol->getAgentCard();

        $this->assertEquals('Test Agent', $card->getId());
        $this->assertEquals('Test Agent', $card->getName());
    }

    public function testCreateTask(): void
    {
        $task = $this->protocol->createTask('Test task', ['priority' => 'high']);

        $this->assertNotEmpty($task->getId());
        $this->assertEquals('Test task', $task->getDescription());
        $this->assertEquals(['priority' => 'high'], $task->getContext());
        $this->assertEquals(TaskState::SUBMITTED, $task->getStatus());

        // Verify that the task creation was logged
        $this->assertTrue($this->logger->hasRecordThatContains('info', 'Task created'));
    }

    public function testHandleGetAgentCardRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'get_agent_card',
            'id' => 1
        ];

        $response = $this->protocol->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(1, $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('Test Agent', $response['result']['name']);
    }

    public function testHandlePingRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'ping',
            'id' => 2
        ];

        $response = $this->protocol->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(2, $response['id']);
        $this->assertEquals(['status' => 'pong'], $response['result']);
    }

    public function testHandleMessageRequest(): void
    {
        $message = Message::createUserMessage('Hello, World!');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'message/send',
            'params' => [
                'from' => 'sender-agent',
                'message' => $message->toArray()
            ],
            'id' => 3
        ];

        $response = $this->protocol->handleRequest($request);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(3, $response['id']);

        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('received', $response['result']['status']);
        $this->assertEquals($message->getMessageId(), $response['result']['message_id']);
        $this->assertArrayHasKey('timestamp', $response['result']);

        // Verify that the message receipt was logged
        $this->assertTrue($this->logger->hasRecordThatContains('info', 'Message received'));
    }

    public function testLogging(): void
    {
        // Test that the logger is working correctly
        $this->logger->info('Test message', ['key' => 'value']);

        $this->assertTrue($this->logger->hasRecord('info', 'Test message'));
        $records = $this->logger->getRecords();
        $this->assertCount(1, $records);
        $this->assertEquals(['key' => 'value'], $records[0]['context']);
    }
}
