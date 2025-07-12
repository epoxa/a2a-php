<?php

declare(strict_types=1);

namespace A2A\Models;

// Legacy Part class for backward compatibility
class Part implements PartInterface
{
    private string $kind;
    private string $content;
    private ?array $metadata = null;

    public function __construct(string $kind, string $content, array $metadata = [])
    {
        $this->kind = $kind;
        $this->content = $content;
        if (!empty($metadata)) {
            $this->metadata = $metadata;
        }
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getContent(): string
    {
        return $this->content;
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
            'content' => $this->content
        ];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['kind'] ?? $data['type'] ?? 'text',
            $data['content'] ?? $data['text'] ?? '',
            $data['metadata'] ?? []
        );
    }

    // Legacy compatibility methods
    public function getType(): string
    {
        return $this->kind;
    }
}