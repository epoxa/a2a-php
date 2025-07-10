<?php

declare(strict_types=1);

namespace A2A\Tests\Models;

use PHPUnit\Framework\TestCase;
use A2A\Models\PushNotificationConfig;

class PushNotificationConfigTest extends TestCase
{
    public function testCreateConfig(): void
    {
        $config = new PushNotificationConfig(
            'https://example.com/webhook',
            'config-123',
            'token-456',
            ['type' => 'bearer']
        );

        $this->assertEquals('https://example.com/webhook', $config->getUrl());
        $this->assertEquals('config-123', $config->getId());
        $this->assertEquals('token-456', $config->getToken());
        $this->assertEquals(['type' => 'bearer'], $config->getAuthentication());
    }

    public function testCreateMinimalConfig(): void
    {
        $config = new PushNotificationConfig('https://example.com/webhook');

        $this->assertEquals('https://example.com/webhook', $config->getUrl());
        $this->assertNull($config->getId());
        $this->assertNull($config->getToken());
        $this->assertEquals([], $config->getAuthentication());
    }

    public function testToArray(): void
    {
        $config = new PushNotificationConfig(
            'https://example.com/webhook',
            'config-123',
            'token-456',
            ['type' => 'bearer']
        );

        $expected = [
            'url' => 'https://example.com/webhook',
            'id' => 'config-123',
            'token' => 'token-456',
            'authentication' => ['type' => 'bearer']
        ];

        $this->assertEquals($expected, $config->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'url' => 'https://example.com/webhook',
            'id' => 'config-789',
            'token' => 'token-abc',
            'authentication' => ['type' => 'api-key']
        ];

        $config = PushNotificationConfig::fromArray($data);

        $this->assertEquals('https://example.com/webhook', $config->getUrl());
        $this->assertEquals('config-789', $config->getId());
        $this->assertEquals('token-abc', $config->getToken());
        $this->assertEquals(['type' => 'api-key'], $config->getAuthentication());
    }
}