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
        $message = new Message('Hello', 'text');
        $this->assertTrue($this->handler->canHandle($message));
    }

    public function testCannotHandleNonTextMessage(): void
    {
        $message = new Message('data', 'binary');
        $this->assertFalse($this->handler->canHandle($message));
    }

    public function testHandleMessage(): void
    {
        $message = new Message('Hello World', 'text');
        $result = $this->handler->handle($message, 'test-agent');

        $this->assertEquals('processed', $result['status']);
        $this->assertEquals('Hello World', $result['echo']);
        $this->assertEquals('test-agent', $result['from']);
        $this->assertEquals($message->getId(), $result['message_id']);
        $this->assertArrayHasKey('timestamp', $result);
    }
}