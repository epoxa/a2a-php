<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * TaskState and accompanying message
 */
class TaskStatus
{
    private TaskState $state;
    private ?MessageV2 $message = null;
    private ?string $timestamp = null;

    public function __construct(TaskState $state)
    {
        $this->state = $state;
        $this->timestamp = date('c'); // ISO 8601 format
    }

    public function getState(): TaskState
    {
        return $this->state;
    }

    public function setState(TaskState $state): void
    {
        $this->state = $state;
        $this->timestamp = date('c');
    }

    public function getMessage(): ?MessageV2
    {
        return $this->message;
    }

    public function setMessage(MessageV2 $message): void
    {
        $this->message = $message;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function toArray(): array
    {
        $result = [
            'state' => $this->state->value
        ];

        if ($this->message !== null) {
            $result['message'] = $this->message->toArray();
        }

        if ($this->timestamp !== null) {
            $result['timestamp'] = $this->timestamp;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $state = TaskState::from($data['state']);
        $status = new self($state);

        if (isset($data['message'])) {
            $status->setMessage(MessageV2::fromArray($data['message']));
        }

        if (isset($data['timestamp'])) {
            $status->setTimestamp($data['timestamp']);
        }

        return $status;
    }
}