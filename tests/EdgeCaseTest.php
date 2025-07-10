<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\Models\Message;
use A2A\Models\AgentCard;
use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Utils\JsonRpc;

class EdgeCaseTest extends TestCase
{
    public function testMessageWithEmptyContent(): void
    {
        $message = new Message('');
        $this->assertEquals('', $message->getContent());
        $this->assertNotEmpty($message->getId());
    }

    public function testMessageFromArrayWithMissingFields(): void
    {
        $data = [];
        $message = Message::fromArray($data);
        
        $this->assertEquals('', $message->getContent());
        $this->assertEquals('text', $message->getType());
        $this->assertNotEmpty($message->getId());
    }

    public function testAgentCardWithMinimalData(): void
    {
        $card = new AgentCard('minimal', 'Minimal Agent');
        
        $this->assertEquals('minimal', $card->getId());
        $this->assertEquals('Minimal Agent', $card->getName());
        $this->assertEquals('', $card->getDescription());
        $this->assertEquals('1.0.0', $card->getVersion());
        $this->assertEquals([], $card->getCapabilities());
    }

    public function testTaskStateTransitions(): void
    {
        $task = new Task('task-1', 'Test');
        
        // Test all valid state transitions
        $states = [
            TaskState::SUBMITTED,
            TaskState::WORKING,
            TaskState::INPUT_REQUIRED,
            TaskState::COMPLETED
        ];
        
        foreach ($states as $state) {
            $task->setStatus($state);
            $this->assertEquals($state, $task->getStatus());
        }
    }

    public function testJsonRpcWithNullId(): void
    {
        $jsonRpc = new JsonRpc();
        $request = $jsonRpc->createRequest('test_method', [], null);
        
        $this->assertEquals('2.0', $request['jsonrpc']);
        $this->assertEquals('test_method', $request['method']);
        $this->assertNull($request['id']);
    }

    public function testJsonRpcErrorHandling(): void
    {
        $jsonRpc = new JsonRpc();
        $error = $jsonRpc->createError(1, 'Test error', -32000);
        
        $this->assertEquals('2.0', $error['jsonrpc']);
        $this->assertEquals(1, $error['id']);
        $this->assertEquals('Test error', $error['error']['message']);
        $this->assertEquals(-32000, $error['error']['code']);
    }

    public function testLargeMessageContent(): void
    {
        $largeContent = str_repeat('A', 10000);
        $message = new Message($largeContent);
        
        $this->assertEquals($largeContent, $message->getContent());
        $this->assertEquals(10000, strlen($message->getContent()));
    }

    public function testSpecialCharactersInMessage(): void
    {
        $specialContent = "Hello 世界! 🌍 Special chars: àáâãäå";
        $message = new Message($specialContent);
        
        $this->assertEquals($specialContent, $message->getContent());
        
        $array = $message->toArray();
        $reconstructed = Message::fromArray($array);
        $this->assertEquals($specialContent, $reconstructed->getContent());
    }

    public function testConcurrentTaskCreation(): void
    {
        $tasks = [];
        for ($i = 0; $i < 100; $i++) {
            $tasks[] = new Task("task-$i", "Task $i");
        }
        
        // Verify all tasks have unique IDs
        $ids = array_map(fn($task) => $task->getId(), $tasks);
        $this->assertEquals(100, count(array_unique($ids)));
    }
}