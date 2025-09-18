<?php

declare(strict_types=1);

namespace A2A\Interfaces;

use A2A\Models\RequestContext;

interface AgentExecutor
{
    /**
     * Executes the agent logic based on the request context and publishes events.
     */
    public function execute(RequestContext $requestContext, ExecutionEventBus $eventBus): void;

    /**
     * Method to explicitly cancel a running task.
     * The implementation should handle the logic of stopping the execution
     * and publishing the final 'canceled' status event on the provided event bus.
     */
    public function cancelTask(string $taskId, ExecutionEventBus $eventBus): void;
}
