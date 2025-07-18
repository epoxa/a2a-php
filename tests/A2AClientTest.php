<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AClient;
use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Utils\HttpClient;
use A2A\Exceptions\A2AException;

class A2AClientTest extends TestCase
{
    private A2AClient $client;
    private AgentCard $agentCard;
    private HttpClient $httpClient;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $capabilities = new \A2A\Models\AgentCapabilities();
        $skill = new \A2A\Models\AgentSkill('test', 'Test', 'Test skill', ['test']);

        $this->agentCard = new AgentCard(
            'Client Agent',
            'Test client agent',
            'https://example.com/agent',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );

        $this->httpClient = $this->createMock(HttpClient::class);
        $this->logger = new TestLogger();

        $this->client = new A2AClient(
            $this->agentCard,
            $this->httpClient,
            $this->logger
        );
    }

    public function testSendMessage(): void
    {
        $message = Message::createUserMessage('Hello World');
        $expectedResponse = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => ['status' => 'received']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'http://example.com/api',
                $this->callback(function ($request) use ($message) {
                    return $request['method'] === 'message/send' &&
                        $request['params']['from'] === 'Client Agent' &&
                        $request['params']['message']['messageId'] === $message->getMessageId();
                })
            )
            ->willReturn($expectedResponse);

        $response = $this->client->sendMessage('http://example.com/api', $message);

        $this->assertEquals($expectedResponse, $response);
        $this->assertTrue($this->logger->hasRecordThatContains('info', 'Message sent'));
    }

    public function testGetAgentCard(): void
    {
        $remoteCardData = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => 'remote-agent-001',
                'name' => 'Remote Agent',
                'description' => 'A remote agent',
                'version' => '1.0.0',
                'capabilities' => ['messaging'],
                'metadata' => []
            ]
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($remoteCardData);

        $remoteCard = $this->client->getAgentCard('http://example.com/api');

        $this->assertEquals('Remote Agent', $remoteCard->getId());
        $this->assertEquals('Remote Agent', $remoteCard->getName());
    }

    public function testPing(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['status' => 'pong']
            ]);

        $result = $this->client->ping('http://example.com/api');

        $this->assertTrue($result);
    }

    public function testPingFailure(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Connection failed'));

        $result = $this->client->ping('http://example.com/api');

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasRecordThatContains('warning', 'Ping failed'));
    }

    public function testSendMessageFailure(): void
    {
        $message = Message::createUserMessage('Hello World');

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(A2AException::class);
        $this->expectExceptionMessage('Failed to send message: Network error');

        $this->client->sendMessage('http://example.com/api', $message);
    }

    public function testGetAgentCardFailure(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Connection timeout'));

        $this->expectException(A2AException::class);
        $this->expectExceptionMessage('Failed to get agent card: Connection timeout');

        $this->client->getAgentCard('http://example.com/api');
    }

    public function testGetTaskSuccess(): void
    {
        $taskData = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => 'task-123',
                'description' => 'Test task',
                'status' => ['state' => 'submitted']
            ]
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($taskData);

        $task = $this->client->getTask('task-123');
        $this->assertNotNull($task);
        $this->assertEquals('task-123', $task->getId());
    }

    public function testGetTaskFailure(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Task service unavailable'));

        $task = $this->client->getTask('task-123');
        $this->assertNull($task);
        $this->assertTrue($this->logger->hasRecordThatContains('error', 'Failed to get task'));
    }

    public function testSendTaskSuccess(): void
    {
        $message = Message::createUserMessage('Process this task');
        $taskData = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => 'task-123',
                'kind' => 'task',
                'status' => ['state' => 'working'],
                'contextId' => 'context-123'
            ]
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with('', $this->callback(function ($request) {
                return $request['method'] === 'tasks/send' &&
                    $request['params']['id'] === 'task-123' &&
                    $request['params']['message']['role'] === 'user';
            }))
            ->willReturn($taskData);

        $task = $this->client->sendTask('task-123', $message, ['priority' => 'high']);
        $this->assertNotNull($task);
        $this->assertEquals('task-123', $task->getId());
        $this->assertEquals('working', $task->getStatus()->value);
    }

    public function testSendTaskFailure(): void
    {
        $message = Message::createUserMessage('Process this task');

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Task creation failed'));

        $this->expectException(A2AException::class);
        $this->expectExceptionMessage('Failed to send task: Task creation failed');

        $this->client->sendTask('task-123', $message);
    }
}
