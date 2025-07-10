<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Exceptions\A2AErrorCodes;
use A2A\Utils\JsonRpc;

class TaskManager
{
    private array $tasks = [];

    public function createTask(string $description, array $context = []): Task
    {
        $taskId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $task = new Task($taskId, $description, $context);
        $this->tasks[$taskId] = $task;
        return $task;
    }

    public function getTask(string $taskId): ?Task
    {
        return $this->tasks[$taskId] ?? null;
    }

    public function cancelTask(string $taskId): array
    {
        $jsonRpc = new JsonRpc();
        $task = $this->getTask($taskId);
        
        if (!$task) {
            return $jsonRpc->createError(null, 'Task not found', A2AErrorCodes::TASK_NOT_FOUND);
        }

        if ($task->isTerminal()) {
            return $jsonRpc->createError(null, 'Task cannot be canceled', A2AErrorCodes::TASK_NOT_CANCELABLE);
        }

        $task->setStatus(TaskState::CANCELED);
        return ['result' => $task->toArray()];
    }

    public function updateTaskStatus(string $taskId, TaskState $status): bool
    {
        $task = $this->getTask($taskId);
        if ($task && !$task->isTerminal()) {
            $task->setStatus($status);
            return true;
        }
        return false;
    }
}