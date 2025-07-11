<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * Interface for all message parts
 */
interface PartInterface
{
    public function getKind(): string;
    public function getMetadata(): ?array;
    public function setMetadata(array $metadata): void;
    public function toArray(): array;
}