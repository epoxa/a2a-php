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

    public function __construct(
        string $content,
        string $type = 'text',
        ?string $id = null,
        array $metadata = []
    ) {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->content = $content;
        $this->type = $type;
        $this->timestamp = new DateTime();
        $this->metadata = $metadata;
        $this->parts = [];
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'type' => $this->type,
            'timestamp' => $this->timestamp->format(DateTimeInterface::ISO8601),
            'metadata' => $this->metadata,
            'parts' => array_map(fn(Part $part) => $part->toArray(), $this->parts)
        ];
    }

    public static function fromArray(array $data): self
    {
        $message = new self(
            $data['content'],
            $data['type'] ?? 'text',
            $data['id'] ?? null,
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
