<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * AgentInterface provides a declaration of a combination of the 
 * target url and the supported transport to interact with the agent.
 */
class AgentInterface
{
    private string $transport;
    private string $url;

    public function __construct(string $transport, string $url)
    {
        $this->transport = $transport;
        $this->url = $url;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function setTransport(string $transport): void
    {
        $this->transport = $transport;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Check if this interface supports JSONRPC
     */
    public function isJsonRpc(): bool
    {
        return strtoupper($this->transport) === 'JSONRPC';
    }

    /**
     * Check if this interface supports GRPC
     */
    public function isGrpc(): bool
    {
        return strtoupper($this->transport) === 'GRPC';
    }

    /**
     * Check if this interface supports HTTP+JSON
     */
    public function isHttpJson(): bool
    {
        return strtoupper($this->transport) === 'HTTP+JSON';
    }

    public function toArray(): array
    {
        return [
            'transport' => $this->transport,
            'url' => $this->url
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['transport'],
            $data['url']
        );
    }

    /**
     * Create a JSONRPC interface
     */
    public static function jsonRpc(string $url): self
    {
        return new self('JSONRPC', $url);
    }

    /**
     * Create a GRPC interface
     */
    public static function grpc(string $url): self
    {
        return new self('GRPC', $url);
    }

    /**
     * Create an HTTP+JSON interface
     */
    public static function httpJson(string $url): self
    {
        return new self('HTTP+JSON', $url);
    }
}
