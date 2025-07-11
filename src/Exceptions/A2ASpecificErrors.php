<?php

declare(strict_types=1);

namespace A2A\Exceptions;

class TaskNotCancelableException extends A2AException
{
    public function __construct(string $message = 'Task cannot be canceled')
    {
        parent::__construct($message);
    }
}

class PushNotificationNotSupportedException extends A2AException
{
    public function __construct(string $message = 'Push notifications not supported')
    {
        parent::__construct($message);
    }
}

class UnsupportedOperationException extends A2AException
{
    public function __construct(string $message = 'Operation not supported')
    {
        parent::__construct($message);
    }
}

class ContentTypeNotSupportedException extends A2AException
{
    public function __construct(string $message = 'Content type not supported')
    {
        parent::__construct($message);
    }
}

class InvalidAgentResponseException extends A2AException
{
    public function __construct(string $message = 'Invalid agent response')
    {
        parent::__construct($message);
    }
}