<?php

declare(strict_types=1);

namespace A2A\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use A2A\Exceptions\A2AException;

class HttpClient
{
    private Client $client;
    private int $timeout;

    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
        $this->client = new Client([
            'timeout' => $timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'A2A-PHP-SDK/1.0.0'
            ]
        ]);
    }

    public function post(string $url, array $data): array
    {
        try {
            $response = $this->client->post($url, [
                'json' => $data
            ]);

            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new A2AException('Invalid JSON response: ' . json_last_error_msg());
            }

            return $decoded;
        } catch (GuzzleException $e) {
            throw new A2AException('HTTP request failed: ' . $e->getMessage());
        }
    }

    public function get(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new A2AException('Invalid JSON response: ' . json_last_error_msg());
            }

            return $decoded;
        } catch (GuzzleException $e) {
            throw new A2AException('HTTP request failed: ' . $e->getMessage());
        }
    }
}
