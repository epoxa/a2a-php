<?php

declare(strict_types=1);

namespace A2A\Utils;

use A2A\Exceptions\InvalidRequestException;

class JsonRpc
{
    private const VERSION = '2.0';

    public function createRequest(string $method, array $params = [], mixed $id = null): array
    {
        $request = [
            'jsonrpc' => self::VERSION,
            'method' => $method,
            'params' => $params
        ];

        if ($id !== null) {
            $request['id'] = $id;
        }

        return $request;
    }

    public function createResponse(mixed $id, mixed $result): array
    {
        return [
            'jsonrpc' => self::VERSION,
            'id' => $id,
            'result' => $result
        ];
    }

    public function createError(mixed $id, string $message, int $code = -32603): array
    {
        return [
            'jsonrpc' => self::VERSION,
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }

    public function parseRequest(array $request): array
    {
        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== self::VERSION) {
            throw new InvalidRequestException('Invalid JSON-RPC version');
        }

        if (!array_key_exists('method', $request)) {
            throw new InvalidRequestException('Missing method');
        }

        if (!is_string($request['method']) || trim($request['method']) === '') {
            throw new InvalidRequestException('Invalid method');
        }

        if (array_key_exists('id', $request) && $request['id'] !== null && !is_string($request['id']) && !is_int($request['id'])) {
            throw new InvalidRequestException('Invalid request id');
        }

        if (isset($request['params']) && !is_array($request['params'])) {
            throw new InvalidRequestException('Invalid params container');
        }

        return [
            'method' => $request['method'],
            'params' => $request['params'] ?? [],
            'id' => $request['id'] ?? null
        ];
    }

    public function isValidRequest(array $request): bool
    {
        try {
            $this->parseRequest($request);
            return true;
        } catch (InvalidRequestException $e) {
            return false;
        }
    }
}
