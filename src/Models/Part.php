<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Factory for creating PartInterface objects from an array.
 */
class Part
{
    public static function fromArray(array $data): PartInterface
    {
        if (!isset($data['kind'])) {
            if (isset($data['type'])) {
                $data['kind'] = $data['type'];
            } else {
                // For backward compatibility, if 'kind' is missing, assume it's a TextPart
                // and the content is in a 'content' or 'text' field.
                $content = $data['content'] ?? $data['text'] ?? '';
                return new TextPart($content, $data['metadata'] ?? null);
            }
        }

        switch ($data['kind']) {
            case 'text':
                return new TextPart($data['text'], $data['metadata'] ?? null);
            case 'file':
                $fileData = $data['file'];
                $uri = $fileData['uri'] ?? $fileData['url'] ?? $fileData['href'] ?? null;

                if (isset($fileData['bytes'])) {
                    $file = new FileWithBytes($fileData['bytes'], $fileData['name'] ?? null, $fileData['mimeType'] ?? null);
                } elseif ($uri !== null) {
                    $file = new FileWithUri($uri, $fileData['name'] ?? null, $fileData['mimeType'] ?? null);
                } else {
                    throw new \InvalidArgumentException('FilePart must have either "bytes" or "uri".');
                }
                return new FilePart($file, $data['metadata'] ?? null);
            case 'data':
                return new DataPart($data['data'], $data['metadata'] ?? null);
            default:
                throw new \InvalidArgumentException("Unknown part kind: {$data['kind']}");
        }
    }
}