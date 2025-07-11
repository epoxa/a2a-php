<?php

declare(strict_types=1);

namespace A2A\Interfaces;

interface ExecutionEventBus
{
    public function publish(object $event): void;
    public function subscribe(string $taskId, callable $callback): void;
    public function unsubscribe(string $taskId): void;
}