<?php

declare(strict_types=1);

namespace A2A\Tests\Models;

use PHPUnit\Framework\TestCase;
use A2A\Models\Message;
use A2A\Models\Part;

class MessageTest extends TestCase
{
    public function testCreateMessage(): void
    {
        $message = new Message('Hello World', 'text');

        $this->assertEquals('Hello World', $message->getContent());
        $this->assertEquals('text', $message->getType());
        $this->assertNotEmpty($message->getId());
        $this->assertInstanceOf(\DateTime::class, $message->getTimestamp());
    }

    public function testCreateMessageWithId(): void
    {
        $message = new Message('Test', 'text', 'custom-id');

        $this->assertEquals('custom-id', $message->getId());
    }

    public function testSetMetadata(): void
    {
        $message = new Message('Test');
        $message->setMetadata('priority', 'high');
        $message->setMetadata('sender', 'agent-123');

        $expected = [
            'priority' => 'high',
            'sender' => 'agent-123'
        ];

        $this->assertEquals($expected, $message->getMetadata());
    }

    public function testAddPart(): void
    {
        $message = new Message('Test');
        $part = new Part('attachment', 'file.pdf');
        $message->addPart($part);

        $this->assertCount(1, $message->getParts());
        $this->assertEquals($part, $message->getParts()[0]);
    }

    public function testToArray(): void
    {
        $message = new Message('Test content', 'text', 'msg-123');
        $message->setMetadata('key', 'value');

        $array = $message->toArray();

        $this->assertEquals('msg-123', $array['id']);
        $this->assertEquals('Test content', $array['content']);
        $this->assertEquals('text', $array['type']);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
        $this->assertEquals([], $array['parts']);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'msg-456',
            'content' => 'Hello',
            'type' => 'greeting',
            'timestamp' => '2023-01-01T12:00:00+00:00',
            'metadata' => ['lang' => 'en'],
            'parts' => []
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('msg-456', $message->getId());
        $this->assertEquals('Hello', $message->getContent());
        $this->assertEquals('greeting', $message->getType());
        $this->assertEquals(['lang' => 'en'], $message->getMetadata());
    }
}
