<?php

declare(strict_types=1);

namespace A2A\Tests;

use PHPUnit\Framework\TestCase;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\AgentProvider;
use A2A\Models\Message;
use A2A\Models\Task;
use A2A\Models\TextPart;
use A2A\Models\FilePart;
use A2A\Models\DataPart;
use A2A\Models\FileWithBytes;
use A2A\Events\ExecutionEventBusImpl;
use A2A\Execution\DefaultAgentExecutor;
use A2A\Models\RequestContext;
use A2A\Exceptions\A2AErrorCodes;

class ComprehensiveTest extends TestCase
{
    public function testCompleteA2AImplementation(): void
    {
        // 1. Test AgentCard compliance
        $capabilities = new AgentCapabilities(true, true, true);
        $skill = new AgentSkill('test', 'Test Skill', 'Test description', ['test']);
        $provider = new AgentProvider('Test Org', 'https://test.com');

        $agentCard = new AgentCard(
            'Test Agent',
            'Test Description',
            'https://example.com/agent',
            '1.0.0',
            $capabilities,
            ['text'],
            ['text'],
            [$skill],
            '0.2.5'
        );
        $agentCard->setProvider($provider);

        $cardArray = $agentCard->toArray();
        $this->assertEquals('0.2.5', $cardArray['protocolVersion']);
        $this->assertTrue($cardArray['capabilities']['streaming']);

        // 2. Test Message compliance
        $message = Message::createUserMessage('Hello World');
        $messageArray = $message->toArray();
        $this->assertEquals('message', $messageArray['kind']);
        $this->assertEquals('user', $messageArray['role']);
        $this->assertArrayHasKey('messageId', $messageArray);

        // 3. Test Task compliance
        $task = new Task('task-123', 'Test task', [], 'ctx-123');
        $taskArray = $task->toArray();
        $this->assertEquals('task', $taskArray['kind']);
        $this->assertEquals('ctx-123', $taskArray['contextId']);

        // 4. Test Part types
        $textPart = new TextPart('Hello');
        $filePart = new FilePart(new FileWithBytes('base64data'));
        $dataPart = new DataPart(['key' => 'value']);

        $this->assertEquals('text', $textPart->getKind());
        $this->assertEquals('file', $filePart->getKind());
        $this->assertEquals('data', $dataPart->getKind());

        // 5. Test Event system
        $eventBus = new ExecutionEventBusImpl();
        $executor = new DefaultAgentExecutor();
        $context = new RequestContext($message, 'task-123', 'ctx-123');

        $events = [];
        $eventBus->subscribe(
            'task-123', function ($event) use (&$events) {
                $events[] = $event;
            }
        );

        $executor->execute($context, $eventBus);
        $this->assertGreaterThan(0, count($events));

        // 6. Test Error codes
        $this->assertEquals(-32002, A2AErrorCodes::TASK_NOT_CANCELABLE);
        $this->assertEquals(-32003, A2AErrorCodes::PUSH_NOTIFICATION_NOT_SUPPORTED);

        $this->assertTrue(true); // All tests passed
    }
}