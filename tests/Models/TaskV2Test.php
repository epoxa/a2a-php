<?php

declare(strict_types=1);

namespace A2A\Tests\Models;

use PHPUnit\Framework\TestCase;
use A2A\Models\Task;
use A2A\Models\TaskState;

class TaskV2Test extends TestCase
{
    public function testCreateTask(): void
    {
        $task = new Task('task-123', 'Test task', [], 'ctx-123');
        
        $this->assertEquals('task-123', $task->getId());
        $this->assertEquals('ctx-123', $task->getContextId());
        $this->assertEquals(TaskState::SUBMITTED, $task->getStatus());
    }

    public function testToArray(): void
    {
        $task = new Task('task-123', 'Test task', [], 'ctx-123');
        $array = $task->toArray();
        
        $this->assertEquals('task', $array['kind']);
        $this->assertEquals('task-123', $array['id']);
        $this->assertEquals('ctx-123', $array['contextId']);
        $this->assertEquals('submitted', $array['status']['state']);
        $this->assertArrayHasKey('timestamp', $array['status']);
    }

    public function testTaskStates(): void
    {
        $task = new Task('task-123', 'Test task');
        
        $task->setStatus(TaskState::WORKING);
        $this->assertEquals(TaskState::WORKING, $task->getStatus());
        
        $task->setStatus(TaskState::COMPLETED);
        $this->assertTrue($task->isTerminal());
    }
}