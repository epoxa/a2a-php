<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\Message;

class A2AServerTest extends TestCase
{
    private A2AServer $server;
    private AgentCard $agentCard;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->agentCard = new AgentCard(
            'server-agent-001',
            'Server Agent',
            'Test server agent'
        );

        $this->logger = new TestLogger();
        $this->server = new A2AServer($this->agentCard, $this->logger);
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
        $this->assertEquals('server-agent-001', $response['result']['id']);
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

        $this->server->addMessageHandler(function ($message, $fromAgent) use (&$messageHandled) {
            $messageHandled = true;
            $this->assertEquals('Hello Server', $message->getContent());
            $this->assertEquals('client-agent', $fromAgent);
        });

        $message = new Message('Hello Server', 'text');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'send_message',
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
        $this->assertEquals($message->getId(), $response['result']['message_id']);
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
        $this->server->addMessageHandler(function ($message, $fromAgent) {
            throw new \Exception('Handler error');
        });

        $message = new Message('Test', 'text');
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'send_message',
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
}
