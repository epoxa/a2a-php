<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Represents a file segment within a message or artifact.
 *
 * @see https://a2a-protocol.org/dev/specification/#652-filepart-object
 */
class FilePart implements PartInterface
{
    /** @var FileWithBytes|FileWithUri */
    private FileBase $file;
    private ?array $metadata;

    /**
     * @param FileWithBytes|FileWithUri $file
     */
    public function __construct(FileBase $file, ?array $metadata = null)
    {
        $this->file = $file;
        $this->metadata = $metadata;
    }

    public function getKind(): string
    {
        return 'file';
    }

    /**
     * @return FileWithBytes|FileWithUri
     */
    public function getFile(): FileBase
    {
        return $this->file;
    }

    public function toArray(): array
    {
        $data = [
            'kind' => 'file',
            'file' => $this->file->toArray(),
        ];

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}