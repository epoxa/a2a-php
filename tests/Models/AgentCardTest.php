<?php

declare(strict_types=1);

namespace A2A\Tests\Models;

use PHPUnit\Framework\TestCase;
use A2A\Models\AgentCard;

class AgentCardTest extends TestCase
{
    public function testCreateAgentCard(): void
    {
        $card = new AgentCard(
            'agent-123',
            'Test Agent',
            'A test agent',
            '2.0.0',
            ['messaging'],
            ['key' => 'value']
        );

        $this->assertEquals('agent-123', $card->getId());
        $this->assertEquals('Test Agent', $card->getName());
        $this->assertEquals('A test agent', $card->getDescription());
        $this->assertEquals('2.0.0', $card->getVersion());
        $this->assertEquals(['messaging'], $card->getCapabilities());
        $this->assertEquals(['key' => 'value'], $card->getMetadata());
    }

    public function testAddCapability(): void
    {
        $card = new AgentCard('agent-123', 'Test Agent');
        $card->addCapability('tasks');
        $card->addCapability('messaging');
        $card->addCapability('tasks'); // Should not duplicate

        $this->assertEquals(['tasks', 'messaging'], $card->getCapabilities());
    }

    public function testHasCapability(): void
    {
        $card = new AgentCard('agent-123', 'Test Agent', '', '1.0.0', ['messaging']);
        
        $this->assertTrue($card->hasCapability('messaging'));
        $this->assertFalse($card->hasCapability('tasks'));
    }

    public function testSetMetadata(): void
    {
        $card = new AgentCard('agent-123', 'Test Agent');
        $card->setMetadata('environment', 'production');
        $card->setMetadata('region', 'us-east-1');

        $expected = [
            'environment' => 'production',
            'region' => 'us-east-1'
        ];

        $this->assertEquals($expected, $card->getMetadata());
    }

    public function testToArray(): void
    {
        $card = new AgentCard(
            'agent-123',
            'Test Agent',
            'Description',
            '1.0.0',
            ['messaging'],
            ['env' => 'test']
        );

        $expected = [
            'id' => 'agent-123',
            'name' => 'Test Agent',
            'description' => 'Description',
            'version' => '1.0.0',
            'capabilities' => ['messaging'],
            'metadata' => ['env' => 'test']
        ];

        $this->assertEquals($expected, $card->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'agent-456',
            'name' => 'Another Agent',
            'description' => 'Another description',
            'version' => '2.1.0',
            'capabilities' => ['tasks', 'messaging'],
            'metadata' => ['type' => 'worker']
        ];

        $card = AgentCard::fromArray($data);

        $this->assertEquals('agent-456', $card->getId());
        $this->assertEquals('Another Agent', $card->getName());
        $this->assertEquals('Another description', $card->getDescription());
        $this->assertEquals('2.1.0', $card->getVersion());
        $this->assertEquals(['tasks', 'messaging'], $card->getCapabilities());
        $this->assertEquals(['type' => 'worker'], $card->getMetadata());
    }
}