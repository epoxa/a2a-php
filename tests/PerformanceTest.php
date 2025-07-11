<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\TaskManager;

class PerformanceTest extends TestCase
{
    public function testHighVolumeMessageProcessing(): void
    {
        $capabilities = new AgentCapabilities();
        $skill = new AgentSkill('test', 'Test', 'Test skill', ['test']);
        
        $agentCard = new AgentCard(
            'Performance Test Agent',
            'Performance test description',
            'https://example.com/perf',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill]
        );
        
        $server = new A2AServer($agentCard);
        
        $processedCount = 0;
        $server->addMessageHandler(function ($message, $fromAgent) use (&$processedCount) {
            $processedCount++;
        });

        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $message = Message::createUserMessage("Message $i");
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'message/send',
                'params' => [
                    'from' => 'client-perf',
                    'message' => $message->toArray()
                ],
                'id' => $i
            ];
            
            $server->handleRequest($request);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $this->assertEquals(1000, $processedCount);
        $this->assertLessThan(5.0, $duration, 'Processing 1000 messages should take less than 5 seconds');
    }

    public function testTaskManagerMemoryUsage(): void
    {
        $taskManager = new TaskManager();
        $initialMemory = memory_get_usage();
        
        // Create many tasks
        for ($i = 0; $i < 1000; $i++) {
            $taskManager->createTask("Task $i", ['index' => $i]);
        }
        
        $afterCreationMemory = memory_get_usage();
        $memoryIncrease = $afterCreationMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 10MB for 1000 tasks)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease);
    }

    public function testMessageSerializationPerformance(): void
    {
        $message = Message::createUserMessage('Performance test message');
        $message->setMetadata(['test' => 'value']);
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10000; $i++) {
            $array = $message->toArray();
            $reconstructed = Message::fromArray($array);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $duration, 'Serialization/deserialization should be fast');
    }
}