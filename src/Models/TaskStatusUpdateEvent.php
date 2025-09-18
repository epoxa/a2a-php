<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Sent by server during sendStream or subscribe requests
 */
class TaskStatusUpdateEvent
{
    private string $kind = 'status-update';
    private string $taskId;
    private string $contextId;
    private TaskStatus $status;
    private bool $final;
    private ?array $metadata = null;

    public function __construct(
        string $taskId,
        string $contextId,
        TaskStatus $status,
        bool $final = false
    ) {
        $this->taskId = $taskId;
        $this->contextId = $contextId;
        $this->status = $status;
        $this->final = $final;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getContextId(): string
    {
        return $this->contextId;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): void
    {
        $this->status = $status;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function setFinal(bool $final): void
    {
        $this->final = $final;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        $result = [
            'kind' => $this->kind,
            'taskId' => $this->taskId,
            'contextId' => $this->contextId,
            'status' => $this->status->toArray(),
            'final' => $this->final
        ];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $status = TaskStatus::fromArray($data['status']);

        $event = new self(
            $data['taskId'],
            $data['contextId'],
            $status,
            $data['final'] ?? false
        );

        if (isset($data['metadata'])) {
            $event->setMetadata($data['metadata']);
        }

        return $event;
    }
}
