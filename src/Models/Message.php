<?php

declare(strict_types=1);

namespace A2A\Models;

use DateTime;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;

class Message
{
    private string $id;
    private string $content;
    private string $type;
    private DateTime $timestamp;
    private array $metadata;
    private array $parts;
    private array $extensions = [];
    private array $referenceTaskIds = [];
    private ?string $contextId = null;
    private ?string $taskId = null;

    public function __construct(
        string $content,
        string $type = 'text',
        ?string $id = null,
        array $metadata = [],
        ?string $contextId = null,
        ?string $taskId = null
    ) {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->content = $content;
        $this->type = $type;
        $this->timestamp = new DateTime();
        $this->metadata = $metadata;
        $this->parts = [];
        $this->contextId = $contextId;
        $this->taskId = $taskId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function addPart(Part $part): void
    {
        $this->parts[] = $part;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function addExtension(string $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function getReferenceTaskIds(): array
    {
        return $this->referenceTaskIds;
    }

    public function addReferenceTaskId(string $taskId): void
    {
        $this->referenceTaskIds[] = $taskId;
    }

    public function getContextId(): ?string
    {
        return $this->contextId;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'type' => $this->type,
            'timestamp' => $this->timestamp->format('c'),
            'metadata' => $this->metadata,
            'parts' => array_map(fn(Part $part) => $part->toArray(), $this->parts)
        ];
    }

    public function toProtocolArray(): array
    {
        return [
            'kind' => 'message',
            'messageId' => $this->id,
            'role' => 'user',
            'parts' => array_map(fn(Part $part) => $part->toArray(), $this->parts),
            'metadata' => $this->metadata,
            'extensions' => $this->extensions,
            'referenceTaskIds' => $this->referenceTaskIds,
            'contextId' => $this->contextId,
            'taskId' => $this->taskId
        ];
    }

    public static function fromArray(array $data): self
    {
        // Handle both protocol format and simple format
        $content = $data['content'] ?? '';
        if (empty($content) && isset($data['parts']) && !empty($data['parts'])) {
            // Extract content from first part if available
            $content = $data['parts'][0]['content'] ?? '';
        }
        
        $message = new self(
            $content,
            $data['type'] ?? 'text',
            $data['id'] ?? $data['messageId'] ?? null,
            $data['metadata'] ?? []
        );

        if (isset($data['timestamp'])) {
            $message->timestamp = new DateTime($data['timestamp']);
        }

        if (isset($data['parts'])) {
            foreach ($data['parts'] as $partData) {
                $message->addPart(Part::fromArray($partData));
            }
        }

        return $message;
    }
}
