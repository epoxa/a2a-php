<?php

declare(strict_types=1);

namespace A2A\Models;

abstract class PartBase
{
    protected ?array $metadata;

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}