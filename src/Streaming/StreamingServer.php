<?php

declare(strict_types=1);

namespace A2A\Streaming;

use A2A\Interfaces\AgentExecutor;
use A2A\Interfaces\ExecutionEventBus;
use A2A\Models\Message;
use A2A\Models\RequestContext;
use A2A\Models\TaskStatusUpdateEvent;
use A2A\Utils\JsonRpc;
use Ramsey\Uuid\Uuid;

class StreamingServer
{
    private SSEStreamer $streamer;
    private JsonRpc $jsonRpc;

    public function __construct()
    {
        $this->streamer = new SSEStreamer();
        $this->jsonRpc = new JsonRpc();
    }

    public function handleStreamRequest(
        array $request,
        AgentExecutor $executor,
        ExecutionEventBus $eventBus
    ): void {
        $this->streamer->startStream();
        $taskId = null;

        try {
            $parsedRequest = $this->jsonRpc->parseRequest($request);

            if (!isset($parsedRequest['params']['message'])) {
                throw new \InvalidArgumentException('Missing message parameter for streaming request');
            }

            $message = Message::fromArray($parsedRequest['params']['message']);

            $taskId = $message->getTaskId() ?? Uuid::uuid4()->toString();
            $contextId = $message->getContextId() ?? Uuid::uuid4()->toString();

            $context = new RequestContext($message, $taskId, $contextId);

            $eventBus->subscribe(
                $taskId,
                function ($event) use ($parsedRequest) {
                    $payload = $this->prepareEventPayload($event);
                    $response = $this->jsonRpc->createResponse($parsedRequest['id'], $payload);

                    $this->streamer->sendEvent(
                        json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        $this->detectEventName($event)
                    );
                }
            );

            $executor->execute($context, $eventBus);
        } catch (\Throwable $e) {
            $error = $this->jsonRpc->createError(
                $request['id'] ?? null,
                $e->getMessage()
            );

            $this->streamer->sendEvent(
                json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'error'
            );
        } finally {
            if ($taskId !== null) {
                $eventBus->unsubscribe($taskId);
            }

            $this->streamer->endStream();
        }
    }

    private function prepareEventPayload(mixed $event): mixed
    {
        if (is_object($event)) {
            if (method_exists($event, 'toArray')) {
                return $event->toArray();
            }

            $encoded = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                return (array) $event;
            }

            $decoded = json_decode($encoded, true);
            return $decoded ?? (array) $event;
        }

        return $event;
    }

    private function detectEventName(mixed $event): ?string
    {
        return match (true) {
            $event instanceof TaskStatusUpdateEvent => 'task-status',
            $event instanceof Message => 'message',
            is_object($event) => 'event',
            default => null,
        };
    }

    public function streamResubscribeError(string $requestId, int $code, string $message, ?string $taskId = null): void
    {
        $this->streamer->startStream();

        try {
            $errorPayload = $this->jsonRpc->createError($requestId, $message, $code);

            $this->streamer->sendEvent(
                json_encode($errorPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'error',
                $taskId
            );
        } finally {
            $this->streamer->endStream();
        }
    }

    public function streamResubscribeSnapshot(string $requestId, string $taskId, array $taskSnapshot): void
    {
        $this->streamer->startStream();

        try {
            if (!empty($taskSnapshot['history']) && is_array($taskSnapshot['history'])) {
                foreach ($taskSnapshot['history'] as $historyEntry) {
                    if (!is_array($historyEntry)) {
                        continue;
                    }

                    $messagePayload = $this->jsonRpc->createResponse($requestId, $historyEntry);
                    $eventId = $historyEntry['messageId'] ?? null;

                    $this->streamer->sendEvent(
                        json_encode($messagePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'message',
                        $eventId
                    );
                }
            }

            $responsePayload = $this->jsonRpc->createResponse($requestId, $taskSnapshot);

            $this->streamer->sendEvent(
                json_encode($responsePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'task-status',
                $taskId
            );
        } finally {
            $this->streamer->endStream();
        }
    }
}
