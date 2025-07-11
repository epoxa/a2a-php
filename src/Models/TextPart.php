<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Represents a text segment within parts
 */
class TextPart implements PartInterface
{
    private string $kind = 'text';
    private string $text;
    private ?array $metadata = null;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
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
            'text' => $this->text
        ];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $part = new self($data['text']);

        if (isset($data['metadata'])) {
            $part->setMetadata($data['metadata']);
        }

        return $part;
    }
}