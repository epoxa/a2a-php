<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Exceptions\A2AErrorCodes;
use A2A\Utils\JsonRpc;
use A2A\Storage\Storage;

class TaskManager
{
    private array $tasks = [];
    private Storage $storage;

    public function __construct(?Storage $storage = null)
    {
        $this->storage = $storage ?? new Storage();
    }

    public function createTask(string $description, array $context = [], ?string $taskId = null): Task
    {
        $taskId = $taskId ?? \Ramsey\Uuid\Uuid::uuid4()->toString();

        // If a custom taskId is provided in context, use it
        if (isset($context['taskId'])) {
            $taskId = $context['taskId'];
            unset($context['taskId']); // Remove from context to avoid duplication
        }

        $task = new Task($taskId, $description, $context);
        $this->tasks[$taskId] = $task;
        $this->storage->saveTask($task);
        return $task;
    }

    public function getTask(string $taskId): ?Task
    {
        // First check in-memory cache
        if (isset($this->tasks[$taskId])) {
            return $this->tasks[$taskId];
        }

        // Then check persistent storage
        $task = $this->storage->getTask($taskId);
        if ($task) {
            $this->tasks[$taskId] = $task; // Cache it
        }
        return $task;
    }

    public function taskExists(string $taskId): bool
    {
        return isset($this->tasks[$taskId]) || $this->storage->taskExists($taskId);
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
        $this->storage->saveTask($task);
        return ['result' => $task->toArray()];
    }

    public function updateTaskStatus(string $taskId, TaskState $status): bool
    {
        $task = $this->getTask($taskId);
        if ($task && !$task->isTerminal()) {
            $task->setStatus($status);
            $this->storage->saveTask($task);
            return true;
        }
        return false;
    }

    public function updateTask(Task $task): void
    {
        $this->tasks[$task->getId()] = $task;
        $this->storage->saveTask($task);
    }
}
