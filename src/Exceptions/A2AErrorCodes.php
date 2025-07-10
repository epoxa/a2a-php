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
    public const TASK_NOT_FOUND = -32000;
    public const TASK_NOT_CANCELABLE = -32001;
    public const PUSH_NOTIFICATION_NOT_SUPPORTED = -32002;
    public const UNSUPPORTED_OPERATION = -32003;
    public const CONTENT_TYPE_NOT_SUPPORTED = -32004;
    public const INVALID_AGENT_RESPONSE = -32005;
}