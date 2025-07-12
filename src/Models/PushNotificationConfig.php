<?php

declare(strict_types=1);

namespace A2A\Models;

class PushNotificationConfig
{
    private string $url;
    private ?string $id;
    private ?string $token;
    private array $authentication;

    public function __construct(
        string $url,
        ?string $id = null,
        ?string $token = null,
        array $authentication = []
    ) {
        $this->url = $url;
        $this->id = $id;
        $this->token = $token;
        $this->authentication = $authentication;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getAuthentication(): array
    {
        return $this->authentication;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'id' => $this->id,
            'token' => $this->token,
            'authentication' => $this->authentication
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['url'],
            $data['id'] ?? null,
            $data['token'] ?? null,
            $data['authentication'] ?? []
        );
    }
}