<?php

declare(strict_types=1);

namespace A2A\Models;

use InvalidArgumentException;

/**
 * Factory for creating File instances from array data
 */
class FileFactory
{
    public static function fromArray(array $data): FileInterface
    {
        if (isset($data['bytes'])) {
            return FileWithBytes::fromArray($data);
        } elseif (isset($data['uri'])) {
            return FileWithUri::fromArray($data);
        } else {
            throw new InvalidArgumentException('File data must contain either "bytes" or "uri" field');
        }
    }
}