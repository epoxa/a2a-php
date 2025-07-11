<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Represents an artifact generated for a task
 */
class Artifact
{
    private string $artifactId;
    private array $parts;
    private ?string $name = null;
    private ?string $description = null;
    private ?array $extensions = null;
    private ?array $metadata = null;

    public function __construct(string $artifactId, array $parts)
    {
        $this->artifactId = $artifactId;
        $this->parts = $parts;
    }

    public function getArtifactId(): string
    {
        return $this->artifactId;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function setParts(array $parts): void
    {
        $this->parts = $parts;
    }

    public function addPart(PartInterface $part): void
    {
        $this->parts[] = $part;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getExtensions(): ?array
    {
        return $this->extensions;
    }

    public function setExtensions(array $extensions): void
    {
        $this->extensions = $extensions;
    }

    public function addExtension(string $extension): void
    {
        if ($this->extensions === null) {
            $this->extensions = [];
        }
        $this->extensions[] = $extension;
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
            'artifactId' => $this->artifactId,
            'parts' => array_map(fn(PartInterface $part) => $part->toArray(), $this->parts)
        ];

        if ($this->name !== null) {
            $result['name'] = $this->name;
        }

        if ($this->description !== null) {
            $result['description'] = $this->description;
        }

        if ($this->extensions !== null) {
            $result['extensions'] = $this->extensions;
        }

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $parts = [];
        if (isset($data['parts'])) {
            foreach ($data['parts'] as $partData) {
                $parts[] = PartFactory::fromArray($partData);
            }
        }

        $artifact = new self($data['artifactId'], $parts);

        if (isset($data['name'])) {
            $artifact->setName($data['name']);
        }

        if (isset($data['description'])) {
            $artifact->setDescription($data['description']);
        }

        if (isset($data['extensions'])) {
            $artifact->setExtensions($data['extensions']);
        }

        if (isset($data['metadata'])) {
            $artifact->setMetadata($data['metadata']);
        }

        return $artifact;
    }
}