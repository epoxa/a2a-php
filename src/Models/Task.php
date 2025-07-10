<?php

declare(strict_types=1);

namespace A2A\Models;

use DateTime;
use DateTimeInterface;

class Task
{
    private string $id;
    private string $description;
    private array $context;
    private string $status;
    private DateTime $createdAt;
    private ?DateTime $completedAt;
    private array $parts;
    private ?string $assignedTo;

    public function __construct(
        string $id,
        string $description,
        array $context = [],
        string $status = 'pending'
    ) {
        $this->id = $id;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        if ($status === 'completed') {
            $this->completedAt = new DateTime();
        }
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
        if ($this->status === 'pending') {
            $this->status = 'assigned';
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'context' => $this->context,
            'status' => $this->status,
            'created_at' => $this->createdAt->format(DateTimeInterface::ISO8601),
            'completed_at' => $this->completedAt?->format(DateTimeInterface::ISO8601),
            'assigned_to' => $this->assignedTo,
            'parts' => array_map(fn(Part $part) => $part->toArray(), $this->parts)
        ];
    }

    public static function fromArray(array $data): self
    {
        $task = new self(
            $data['id'],
            $data['description'],
            $data['context'] ?? [],
            $data['status'] ?? 'pending'
        );

        if (isset($data['created_at'])) {
            $task->createdAt = new DateTime($data['created_at']);
        }

        if (isset($data['completed_at'])) {
            $task->completedAt = new DateTime($data['completed_at']);
        }

        if (isset($data['assigned_to'])) {
            $task->assignedTo = $data['assigned_to'];
        }

        if (isset($data['parts'])) {
            foreach ($data['parts'] as $partData) {
                $task->addPart(Part::fromArray($partData));
            }
        }

        return $task;
    }
}
