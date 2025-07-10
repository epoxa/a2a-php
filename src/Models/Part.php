<?php

declare(strict_types=1);

namespace A2A\Models;

class Part
{
    private string $type;
    private string $content;
    private array $metadata;

    public function __construct(string $type, string $content, array $metadata = [])
    {
        $this->type = $type;
        $this->content = $content;
        $this->metadata = $metadata;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'content' => $this->content,
            'metadata' => $this->metadata
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'],
            $data['content'],
            $data['metadata'] ?? []
        );
    }
}