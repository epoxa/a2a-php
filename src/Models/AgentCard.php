<?php

declare(strict_types=1);

namespace A2A\Models;

class AgentCard
{
    private string $id;
    private string $name;
    private string $description;
    private string $version;
    private array $capabilities;
    private array $metadata;
    private array $extensions = [];
    private array $additionalInterfaces = [];
    private bool $supportsAuthenticatedExtendedCard = false;

    public function __construct(
        string $id,
        string $name,
        string $description = '',
        string $version = '1.0.0',
        array $capabilities = [],
        array $metadata = [],
        array $extensions = [],
        bool $supportsAuthenticatedExtendedCard = false
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->version = $version;
        $this->capabilities = $capabilities;
        $this->metadata = $metadata;
        $this->extensions = $extensions;
        $this->supportsAuthenticatedExtendedCard = $supportsAuthenticatedExtendedCard;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function addCapability(string $capability): void
    {
        if (!in_array($capability, $this->capabilities)) {
            $this->capabilities[] = $capability;
        }
    }

    public function setMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities);
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function addExtension(array $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function getAdditionalInterfaces(): array
    {
        return $this->additionalInterfaces;
    }

    public function addInterface(string $url, string $transport): void
    {
        $this->additionalInterfaces[] = ['url' => $url, 'transport' => $transport];
    }

    public function supportsAuthenticatedExtendedCard(): bool
    {
        return $this->supportsAuthenticatedExtendedCard;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'capabilities' => $this->capabilities,
            'metadata' => $this->metadata
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['description'] ?? '',
            $data['version'] ?? '1.0.0',
            $data['capabilities'] ?? [],
            $data['metadata'] ?? []
        );
    }
}
