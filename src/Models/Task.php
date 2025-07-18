<?php

declare(strict_types=1);

namespace A2A\Models;

use DateTime;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;

class Task
{
    private string $id;
    private string $contextId;
    private string $description;
    private array $context;
    private TaskState $status;
    private DateTime $createdAt;
    private ?DateTime $completedAt;
    private array $parts;
    private ?string $assignedTo;
    private array $history = [];
    private array $artifacts = [];

    public function __construct(
        string $id,
        string $description,
        array $context = [],
        ?string $contextId = null,
        TaskState $status = TaskState::SUBMITTED
    ) {
        $this->id = $id;
        $this->contextId = $contextId ?? Uuid::uuid4()->toString();
        $this->description = $description;
        $this->context = $context;
        $this->status = $status;
        $this->createdAt = new DateTime();
        $this->completedAt = null;
        $this->parts = [];
        $this->assignedTo = null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextId(): string
    {
        return $this->contextId;
    }

    public function getStatus(): TaskState
    {
        return $this->status;
    }

    public function setStatus(TaskState $status): void
    {
        $this->status = $status;
        if ($status === TaskState::COMPLETED) {
            $this->completedAt = new DateTime();
        }
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function addPart(Part $part): void
    {
        $this->parts[] = $part;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function assignTo(string $agentId): void
    {
        $this->assignedTo = $agentId;
        if ($this->status === TaskState::SUBMITTED) {
            $this->status = TaskState::WORKING;
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === TaskState::COMPLETED;
    }

    public function addToHistory(Message $message): void
    {
        $this->history[] = $message;
    }

    public function getHistory(int $limit = null): array
    {
        if ($limit === null) {
            return $this->history;
        }
        return array_slice($this->history, -$limit);
    }

    public function addArtifact(array $artifact): void
    {
        $this->artifacts[] = $artifact;
    }

    public function getArtifacts(): array
    {
        return $this->artifacts;
    }

    public function toArray(): array
    {
        $result = [
            'kind' => 'task',
            'id' => $this->id,
            'contextId' => $this->contextId,
            'status' => [
                'state' => $this->status->value,
                'timestamp' => $this->completedAt?->format(DateTimeInterface::ISO8601) ?? $this->createdAt->format(DateTimeInterface::ISO8601)
            ]
        ];

        if (!empty($this->artifacts)) {
            $result['artifacts'] = $this->artifacts;
        }

        if (!empty($this->history)) {
            $result['history'] = array_map(fn($msg) => $msg->toArray(), $this->history);
        }

        if (!empty($this->context)) {
            $result['metadata'] = $this->context;
        }

        return $result;
    }

    public function toArrayWithHistory(?int $historyLength = null): array
    {
        $result = [
            'kind' => 'task',
            'id' => $this->id,
            'contextId' => $this->contextId,
            'status' => [
                'state' => $this->status->value,
                'timestamp' => $this->completedAt?->format(DateTimeInterface::ISO8601) ?? $this->createdAt->format(DateTimeInterface::ISO8601)
            ]
        ];

        if (!empty($this->artifacts)) {
            $result['artifacts'] = $this->artifacts;
        }

        // Include history when historyLength is specified, even if empty
        if ($historyLength !== null) {
            $historyItems = $historyLength > 0 ? $this->getHistory($historyLength) : $this->getHistory();
            $result['history'] = array_map(fn($msg) => $msg->toArray(), $historyItems);
        } elseif (!empty($this->history)) {
            $result['history'] = array_map(fn($msg) => $msg->toArray(), $this->history);
        }

        if (!empty($this->context)) {
            $result['metadata'] = $this->context;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $status = TaskState::SUBMITTED;
        if (isset($data['status']['state'])) {
            $status = TaskState::from($data['status']['state']);
        }

        $task = new self(
            $data['id'],
            $data['description'] ?? '',
            $data['metadata'] ?? [],
            $data['contextId'] ?? null,
            $status
        );

        if (isset($data['artifacts'])) {
            foreach ($data['artifacts'] as $artifact) {
                $task->addArtifact($artifact);
            }
        }

        if (isset($data['history'])) {
            foreach ($data['history'] as $messageData) {
                $task->addToHistory(Message::fromArray($messageData));
            }
        }

        return $task;
    }
}
