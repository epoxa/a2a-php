<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * File content with base64 encoded bytes
 */
class FileWithBytes implements FileInterface
{
    private string $bytes;
    private ?string $name = null;
    private ?string $mimeType = null;

    public function __construct(string $bytes)
    {
        $this->bytes = $bytes;
    }

    public function getBytes(): string
    {
        return $this->bytes;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function toArray(): array
    {
        $result = [
            'bytes' => $this->bytes
        ];

        if ($this->name !== null) {
            $result['name'] = $this->name;
        }

        if ($this->mimeType !== null) {
            $result['mimeType'] = $this->mimeType;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $file = new self($data['bytes']);

        if (isset($data['name'])) {
            $file->setName($data['name']);
        }

        if (isset($data['mimeType'])) {
            $file->setMimeType($data['mimeType']);
        }

        return $file;
    }
}