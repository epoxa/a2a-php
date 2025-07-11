<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Sent by server during sendStream or subscribe requests
 */
class TaskArtifactUpdateEvent
{
    private string $kind = 'artifact-update';
    private string $taskId;
    private string $contextId;
    private Artifact $artifact;
    private ?bool $append = null;
    private ?bool $lastChunk = null;
    private ?array $metadata = null;

    public function __construct(
        string $taskId,
        string $contextId,
        Artifact $artifact
    ) {
        $this->taskId = $taskId;
        $this->contextId = $contextId;
        $this->artifact = $artifact;
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

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function setArtifact(Artifact $artifact): void
    {
        $this->artifact = $artifact;
    }

    public function getAppend(): ?bool
    {
        return $this->append;
    }

    public function setAppend(bool $append): void
    {
        $this->append = $append;
    }

    public function getLastChunk(): ?bool
    {
        return $this->lastChunk;
    }

    public function setLastChunk(bool $lastChunk): void
    {
        $this->lastChunk = $lastChunk;
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
            'artifact' => $this->artifact->toArray()
        ];

        if ($this->append !== null) {
            $result['append'] = $this->append;
        }

        if ($this->lastChunk !== null) {
            $result['lastChunk'] = $this->lastChunk;
        }

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $artifact = Artifact::fromArray($data['artifact']);
        
        $event = new self(
            $data['taskId'],
            $data['contextId'],
            $artifact
        );

        if (isset($data['append'])) {
            $event->setAppend($data['append']);
        }

        if (isset($data['lastChunk'])) {
            $event->setLastChunk($data['lastChunk']);
        }

        if (isset($data['metadata'])) {
            $event->setMetadata($data['metadata']);
        }

        return $event;
    }
}