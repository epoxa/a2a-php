<?php

declare(strict_types=1);

namespace A2A\Tests\Models;

use PHPUnit\Framework\TestCase;
use A2A\Models\Part;

class PartTest extends TestCase
{
    public function testCreatePart(): void
    {
        $part = new Part('text', 'Hello World', ['lang' => 'en']);

        $this->assertEquals('text', $part->getType());
        $this->assertEquals('Hello World', $part->getContent());
        $this->assertEquals(['lang' => 'en'], $part->getMetadata());
    }

    public function testSetMetadata(): void
    {
        $part = new Part('text', 'Hello');
        $part->setMetadata('priority', 'high');

        $this->assertEquals(['priority' => 'high'], $part->getMetadata());
    }

    public function testToArray(): void
    {
        $part = new Part('image', 'base64data', ['format' => 'png']);
        $array = $part->toArray();

        $expected = [
            'type' => 'image',
            'content' => 'base64data',
            'metadata' => ['format' => 'png']
        ];

        $this->assertEquals($expected, $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'type' => 'file',
            'content' => 'file content',
            'metadata' => ['size' => 1024]
        ];

        $part = Part::fromArray($data);

        $this->assertEquals('file', $part->getType());
        $this->assertEquals('file content', $part->getContent());
        $this->assertEquals(['size' => 1024], $part->getMetadata());
    }
}