<?php

declare(strict_types=1);

namespace A2A\Streaming;

class SSEStreamer
{
    public function sendEvent(string $data, ?string $event = null, ?string $id = null): void
    {
        if ($id !== null) {
            echo "id: $id\n";
        }

        if ($event !== null) {
            echo "event: $event\n";
        }

        echo "data: $data\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    public function startStream(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    public function endStream(): void
    {
        echo "event: close\ndata: \n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
