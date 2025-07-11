<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Represents a File segment within parts
 */
class FilePart implements PartInterface
{
    private string $kind = 'file';
    private FileInterface $file;
    private ?array $metadata = null;

    public function __construct(FileInterface $file)
    {
        $this->file = $file;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
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
            'file' => $this->file->toArray()
        ];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $file = FileFactory::fromArray($data['file']);
        $part = new self($file);

        if (isset($data['metadata'])) {
            $part->setMetadata($data['metadata']);
        }

        return $part;
    }
}