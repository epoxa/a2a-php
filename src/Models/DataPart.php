<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Represents a structured data segment within a message part
 */
class DataPart implements PartInterface
{
    private string $kind = 'data';
    private array $data;
    private ?array $metadata = null;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
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
            'data' => $this->data
        ];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $part = new self($data['data']);

        if (isset($data['metadata'])) {
            $part->setMetadata($data['metadata']);
        }

        return $part;
    }
}