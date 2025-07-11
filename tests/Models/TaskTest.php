<?php

declare(strict_types=1);

namespace A2A\Tests\Models;

use PHPUnit\Framework\TestCase;
use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Models\Message;
use A2A\Models\Part;

class TaskTest extends TestCase
{
    public function testCreateTask(): void
    {
        $task = new Task('task-123', 'Test task', ['priority' => 'high']);

        $this->assertEquals('task-123', $task->getId());
        $this->assertEquals('Test task', $task->getDescription());
        $this->assertEquals(['priority' => 'high'], $task->getContext());
        $this->assertEquals(TaskState::SUBMITTED, $task->getStatus());
        $this->assertFalse($task->isCompleted());
        $this->assertFalse($task->isTerminal());
    }

    public function testTaskStatusTransitions(): void
    {
        $task = new Task('task-123', 'Test task');

        $task->setStatus(TaskState::WORKING);
        $this->assertEquals(TaskState::WORKING, $task->getStatus());

        $task->setStatus(TaskState::COMPLETED);
        $this->assertEquals(TaskState::COMPLETED, $task->getStatus());
        $this->assertTrue($task->isCompleted());
        $this->assertTrue($task->isTerminal());
        $this->assertNotNull($task->getCompletedAt());
    }

    public function testAddPart(): void
    {
        $task = new Task('task-123', 'Test task');
        $part = new Part('text', 'Hello World');
        $task->addPart($part);

        $this->assertCount(1, $task->getParts());
        $this->assertEquals($part, $task->getParts()[0]);
    }

    public function testTaskHistory(): void
    {
        $task = new Task('task-123', 'Test task');
        $message1 = Message::createUserMessage('First message');
        $message2 = Message::createUserMessage('Second message');

        $task->addToHistory($message1);
        $task->addToHistory($message2);

        $this->assertCount(2, $task->getHistory());
        $this->assertCount(1, $task->getHistory(1));
        $this->assertEquals($message2, $task->getHistory(1)[0]);
    }

    public function testTaskArtifacts(): void
    {
        $task = new Task('task-123', 'Test task');
        $artifact = ['type' => 'file', 'name' => 'output.txt'];
        $task->addArtifact($artifact);

        $this->assertCount(1, $task->getArtifacts());
        $this->assertEquals($artifact, $task->getArtifacts()[0]);
    }

    public function testToArray(): void
    {
        $task = new Task('task-123', 'Test task', ['key' => 'value']);
        $array = $task->toArray();

        $this->assertEquals('task', $array['kind']);
        $this->assertEquals('task-123', $array['id']);
        $this->assertEquals('submitted', $array['status']['state']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'task-456',
            'description' => 'Another task',
            'metadata' => ['type' => 'test'],
            'status' => ['state' => 'working']
        ];

        $task = Task::fromArray($data);

        $this->assertEquals('task-456', $task->getId());
        $this->assertEquals('Another task', $task->getDescription());
        $this->assertEquals(TaskState::WORKING, $task->getStatus());
        $this->assertEquals(['type' => 'test'], $task->getContext());
    }
}