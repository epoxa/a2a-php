<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * A declaration of an extension supported by an Agent
 */
class AgentExtension
{
    private string $uri;
    private ?string $description = null;
    private bool $required = false;
    private ?array $params = null;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function toArray(): array
    {
        $result = [
            'uri' => $this->uri,
            'required' => $this->required
        ];

        if ($this->description !== null) {
            $result['description'] = $this->description;
        }

        if ($this->params !== null) {
            $result['params'] = $this->params;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $extension = new self($data['uri']);

        if (isset($data['description'])) {
            $extension->setDescription($data['description']);
        }

        if (isset($data['required'])) {
            $extension->setRequired($data['required']);
        }

        if (isset($data['params'])) {
            $extension->setParams($data['params']);
        }

        return $extension;
    }
}