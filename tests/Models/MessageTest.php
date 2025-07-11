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
        $message = Message::createUserMessage('Hello World');

        $this->assertEquals('Hello World', $message->getTextContent());
        $this->assertEquals('user', $message->getRole());
        $this->assertNotEmpty($message->getMessageId());
    }

    public function testCreateMessageWithId(): void
    {
        $message = Message::createUserMessage('Test', 'custom-id');

        $this->assertEquals('custom-id', $message->getMessageId());
    }

    public function testSetMetadata(): void
    {
        $message = Message::createUserMessage('Test');
        $message->setMetadata(['priority' => 'high', 'sender' => 'agent-123']);

        $expected = [
            'priority' => 'high',
            'sender' => 'agent-123'
        ];

        $this->assertEquals($expected, $message->getMetadata());
    }

    public function testAddPart(): void
    {
        $message = Message::createUserMessage('Test');
        $part = new Part('attachment', 'file.pdf');
        $message->addPart($part);

        $this->assertCount(2, $message->getParts()); // 1 text part + 1 added part
    }

    public function testToArray(): void
    {
        $message = Message::createUserMessage('Test content', 'msg-123');
        $message->setMetadata(['key' => 'value']);

        $array = $message->toArray();

        $this->assertEquals('msg-123', $array['messageId']);
        $this->assertEquals('message', $array['kind']);
        $this->assertEquals('user', $array['role']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
        $this->assertCount(1, $array['parts']);
    }

    public function testFromArray(): void
    {
        $data = [
            'messageId' => 'msg-456',
            'role' => 'user',
            'parts' => [
                ['kind' => 'text', 'text' => 'Hello']
            ],
            'metadata' => ['lang' => 'en']
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('msg-456', $message->getMessageId());
        $this->assertEquals('Hello', $message->getTextContent());
        $this->assertEquals(['lang' => 'en'], $message->getMetadata());
    }

    public function testMessageExtensions(): void
    {
        $message = Message::createUserMessage('Test');
        $message->setExtensions(['extension1', 'extension2']);

        $this->assertEquals(['extension1', 'extension2'], $message->getExtensions());
    }

    public function testReferenceTaskIds(): void
    {
        $message = Message::createUserMessage('Test');
        $message->setReferenceTaskIds(['task-1', 'task-2']);

        $this->assertEquals(['task-1', 'task-2'], $message->getReferenceTaskIds());
    }

    public function testContextAndTaskIds(): void
    {
        $message = Message::createUserMessage('Test');
        $message->setContextId('context-123');
        $message->setTaskId('task-456');

        $this->assertEquals('context-123', $message->getContextId());
        $this->assertEquals('task-456', $message->getTaskId());
    }
}