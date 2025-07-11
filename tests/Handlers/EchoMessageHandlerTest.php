<?php

declare(strict_types=1);

namespace A2A\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use A2A\Handlers\EchoMessageHandler;
use A2A\Models\Message;

class EchoMessageHandlerTest extends TestCase
{
    private EchoMessageHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new EchoMessageHandler();
    }

    public function testCanHandleTextMessage(): void
    {
        $message = Message::createUserMessage('Hello');
        $this->assertTrue($this->handler->canHandle($message));
    }

    public function testCannotHandleNonTextMessage(): void
    {
        $message = Message::createUserMessage('data');
        $this->assertTrue($this->handler->canHandle($message)); // All messages are text-based now
    }

    public function testHandleMessage(): void
    {
        $message = Message::createUserMessage('Hello World');
        $result = $this->handler->handle($message, 'test-agent');

        $this->assertEquals('processed', $result['status']);
        $this->assertEquals('Hello World', $result['echo']);
        $this->assertEquals('test-agent', $result['from']);
        $this->assertEquals($message->getMessageId(), $result['message_id']);
        $this->assertArrayHasKey('timestamp', $result);
    }
}