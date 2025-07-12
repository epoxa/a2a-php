<?php

declare(strict_types=1);

namespace A2A\Execution;

use A2A\Interfaces\AgentExecutor;
use A2A\Interfaces\ExecutionEventBus;
use A2A\Models\RequestContext;
use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Models\TaskStatusUpdateEvent;
use A2A\Models\TaskStatus;

class DefaultAgentExecutor implements AgentExecutor
{
    private array $cancelledTasks = [];

    public function execute(RequestContext $requestContext, ExecutionEventBus $eventBus): void
    {
        $taskId = $requestContext->taskId;

        // Publish initial task if new
        if (!$requestContext->task) {
            $task = new Task($taskId, 'Processing message', [], $requestContext->contextId);
            $eventBus->publish($task);
        }

        // Publish working status
        $workingStatus = new TaskStatus(TaskState::WORKING);
        $workingEvent = new TaskStatusUpdateEvent($taskId, $requestContext->contextId, $workingStatus);
        $eventBus->publish($workingEvent);

        // Check for cancellation
        if (in_array($taskId, $this->cancelledTasks)) {
            $cancelledStatus = new TaskStatus(TaskState::CANCELED);
            $cancelledEvent = new TaskStatusUpdateEvent($taskId, $requestContext->contextId, $cancelledStatus, true);
            $eventBus->publish($cancelledEvent);
            return;
        }

        // Simulate processing
        sleep(1);

        // Publish completion
        $completedStatus = new TaskStatus(TaskState::COMPLETED);
        $completedEvent = new TaskStatusUpdateEvent($taskId, $requestContext->contextId, $completedStatus, true);
        $eventBus->publish($completedEvent);
    }

    public function cancelTask(string $taskId, ExecutionEventBus $eventBus): void
    {
        $this->cancelledTasks[] = $taskId;
    }
}