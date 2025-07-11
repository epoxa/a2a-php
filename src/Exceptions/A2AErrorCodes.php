<?php

declare(strict_types=1);

namespace A2A\Exceptions;

class A2AErrorCodes
{
    // JSON-RPC standard error codes
    public const PARSE_ERROR = -32700;
    public const INVALID_REQUEST = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INVALID_PARAMS = -32602;
    public const INTERNAL_ERROR = -32603;

    // A2A specific error codes
    public const TASK_NOT_FOUND = -32001;
    public const TASK_NOT_CANCELABLE = -32002;
    public const PUSH_NOTIFICATION_NOT_SUPPORTED = -32003;
    public const UNSUPPORTED_OPERATION = -32004;
    public const CONTENT_TYPE_NOT_SUPPORTED = -32005;
    public const INVALID_AGENT_RESPONSE = -32006;

    public static function getErrorMessage(int $code): string
    {
        return match ($code) {
            self::PARSE_ERROR => 'Parse error',
            self::INVALID_REQUEST => 'Invalid Request',
            self::METHOD_NOT_FOUND => 'Method not found',
            self::INVALID_PARAMS => 'Invalid params',
            self::INTERNAL_ERROR => 'Internal error',
            self::TASK_NOT_FOUND => 'Task not found',
            self::TASK_NOT_CANCELABLE => 'Task not cancelable',
            self::PUSH_NOTIFICATION_NOT_SUPPORTED => 'Push notification not supported',
            self::UNSUPPORTED_OPERATION => 'Unsupported operation',
            self::CONTENT_TYPE_NOT_SUPPORTED => 'Content type not supported',
            self::INVALID_AGENT_RESPONSE => 'Invalid agent response',
            default => 'Unknown error'
        };
    }
}
