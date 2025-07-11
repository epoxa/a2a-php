<?php

declare(strict_types=1);

namespace A2A\Handlers;

use A2A\Interfaces\MessageHandlerInterface;
use A2A\Models\Message;

class EchoMessageHandler implements MessageHandlerInterface
{
    public function handle(Message $message, string $fromAgent): array
    {
        return [
            'status' => 'processed',
            'echo' => $message->getTextContent(),
            'from' => $fromAgent,
            'message_id' => $message->getMessageId(),
            'timestamp' => time()
        ];
    }

    public function canHandle(Message $message): bool
    {
        return true; // All messages have text content
    }
}