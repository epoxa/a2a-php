<?php

declare(strict_types=1);

namespace A2A\Models;

class RequestContext
{
    public readonly Message $userMessage;
    public readonly ?Task $task;
    public readonly ?array $referenceTasks;
    public readonly string $taskId;
    public readonly string $contextId;

    public function __construct(
        Message $userMessage,
        string $taskId,
        string $contextId,
        ?Task $task = null,
        ?array $referenceTasks = null
    ) {
        $this->userMessage = $userMessage;
        $this->taskId = $taskId;
        $this->contextId = $contextId;
        $this->task = $task;
        $this->referenceTasks = $referenceTasks;
    }
}