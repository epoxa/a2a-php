<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\Models\Message;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Utils\JsonRpc;

class EdgeCaseTest extends TestCase
{
    public function testMessageWithEmptyContent(): void
    {
        $message = Message::createUserMessage('');
        $this->assertEquals('', $message->getTextContent());
        $this->assertNotEmpty($message->getMessageId());
    }

    public function testMessageFromArrayWithMissingFields(): void
    {
        $data = ['messageId' => 'test-123', 'role' => 'user', 'parts' => []];
        $message = Message::fromArray($data);
        
        $this->assertEquals('test-123', $message->getMessageId());
        $this->assertEquals('user', $message->getRole());
        $this->assertNotEmpty($message->getMessageId());
    }

    public function testAgentCardWithMinimalData(): void
    {
        $capabilities = new AgentCapabilities();
        $skill = new AgentSkill('test', 'Test', 'Test skill', ['test']);
        
        $card = new AgentCard(
            'Minimal Agent',
            'Description',
            'https://example.com/agent',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );
        
        $this->assertEquals('Minimal Agent', $card->getName());
        $this->assertEquals('Description', $card->getDescription());
        $this->assertEquals('1.0.0', $card->getVersion());
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
        $message = Message::createUserMessage($largeContent);
        
        $this->assertEquals($largeContent, $message->getTextContent());
        $this->assertEquals(10000, strlen($message->getTextContent()));
    }

    public function testSpecialCharactersInMessage(): void
    {
        $specialContent = "Hello 世界! 🌍 Special chars: àáâãäå";
        $message = Message::createUserMessage($specialContent);
        
        $this->assertEquals($specialContent, $message->getTextContent());
        
        $array = $message->toArray();
        $reconstructed = Message::fromArray($array);
        $this->assertEquals($specialContent, $reconstructed->getTextContent());
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