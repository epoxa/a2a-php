<?php

declare(strict_types=1);

namespace A2A\Events;

use A2A\Interfaces\ExecutionEventBus;

class ExecutionEventBusImpl implements ExecutionEventBus
{
    private array $subscribers = [];

    public function publish(object $event): void
    {
        $taskId = $this->getTaskIdFromEvent($event);
        if ($taskId && isset($this->subscribers[$taskId])) {
            foreach ($this->subscribers[$taskId] as $callback) {
                $callback($event);
            }
        }
    }

    public function subscribe(string $taskId, callable $callback): void
    {
        $this->subscribers[$taskId][] = $callback;
    }

    public function unsubscribe(string $taskId): void
    {
        unset($this->subscribers[$taskId]);
    }

    private function getTaskIdFromEvent(object $event): ?string
    {
        return match (true) {
            $event instanceof \A2A\Models\Task => $event->getId(),
            $event instanceof \A2A\Models\TaskStatusUpdateEvent => $event->getTaskId(),
            $event instanceof \A2A\Models\TaskArtifactUpdateEvent => $event->getTaskId(),
            default => null
        };
    }
}
