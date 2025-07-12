<?php

declare(strict_types=1);

namespace A2A\Streaming;

use A2A\Models\Message;
use A2A\Models\Task;
use A2A\Interfaces\ExecutionEventBus;
use A2A\Interfaces\AgentExecutor;
use A2A\Models\RequestContext;
use A2A\Utils\JsonRpc;

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

        try {
            $parsedRequest = $this->jsonRpc->parseRequest($request);
            $message = Message::fromArray($parsedRequest['params']['message']);

            $taskId = $message->getTaskId() ?? \Ramsey\Uuid\Uuid::uuid4()->toString();
            $contextId = $message->getContextId() ?? \Ramsey\Uuid\Uuid::uuid4()->toString();

            $context = new RequestContext($message, $taskId, $contextId);

            // Subscribe to events for this task
            $eventBus->subscribe($taskId, function ($event) use ($parsedRequest) {
                $response = $this->jsonRpc->createResponse($parsedRequest['id'], $event);
                $this->streamer->sendEvent(json_encode($response));
            });

            // Execute the task
            $executor->execute($context, $eventBus);
        } catch (\Exception $e) {
            $error = $this->jsonRpc->createError($request['id'] ?? null, $e->getMessage());
            $this->streamer->sendEvent(json_encode($error));
        } finally {
            $this->streamer->endStream();
        }
    }
}
