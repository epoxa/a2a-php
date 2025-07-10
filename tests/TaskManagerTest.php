<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\TaskManager;
use A2A\Models\TaskState;

class TaskManagerTest extends TestCase
{
    private TaskManager $taskManager;

    protected function setUp(): void
    {
        $this->taskManager = new TaskManager();
    }

    public function testCreateTask(): void
    {
        $task = $this->taskManager->createTask('Test task', ['priority' => 'high']);

        $this->assertNotEmpty($task->getId());
        $this->assertEquals('Test task', $task->getDescription());
        $this->assertEquals(['priority' => 'high'], $task->getContext());
        $this->assertEquals(TaskState::SUBMITTED, $task->getStatus());
    }

    public function testGetTask(): void
    {
        $task = $this->taskManager->createTask('Test task');
        $retrieved = $this->taskManager->getTask($task->getId());

        $this->assertEquals($task, $retrieved);
    }

    public function testGetNonExistentTask(): void
    {
        $result = $this->taskManager->getTask('non-existent');
        $this->assertNull($result);
    }

    public function testCancelTask(): void
    {
        $task = $this->taskManager->createTask('Test task');
        $result = $this->taskManager->cancelTask($task->getId());

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals(TaskState::CANCELED, $task->getStatus());
    }

    public function testCancelNonExistentTask(): void
    {
        $result = $this->taskManager->cancelTask('non-existent');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Task not found', $result['error']['message']);
    }

    public function testCancelCompletedTask(): void
    {
        $task = $this->taskManager->createTask('Test task');
        $task->setStatus(TaskState::COMPLETED);
        
        $result = $this->taskManager->cancelTask($task->getId());

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('cannot be canceled', $result['error']['message']);
    }

    public function testUpdateTaskStatus(): void
    {
        $task = $this->taskManager->createTask('Test task');
        $result = $this->taskManager->updateTaskStatus($task->getId(), TaskState::WORKING);

        $this->assertTrue($result);
        $this->assertEquals(TaskState::WORKING, $task->getStatus());
    }

    public function testUpdateNonExistentTaskStatus(): void
    {
        $result = $this->taskManager->updateTaskStatus('non-existent', TaskState::WORKING);
        $this->assertFalse($result);
    }

    public function testUpdateTerminalTaskStatus(): void
    {
        $task = $this->taskManager->createTask('Test task');
        $task->setStatus(TaskState::COMPLETED);
        
        $result = $this->taskManager->updateTaskStatus($task->getId(), TaskState::WORKING);
        $this->assertFalse($result);
    }
}