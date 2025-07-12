<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\Models\RequestContext;
use A2A\Events\ExecutionEventBusImpl;
use A2A\Execution\DefaultAgentExecutor;
use A2A\Streaming\SSEStreamer;

echo "=== A2A Streaming and Events Example ===\n\n";

// 1. Create agent with streaming capabilities
$capabilities = new AgentCapabilities(
    streaming: true,
    pushNotifications: false,
    stateTransitionHistory: true
);

$skill = new AgentSkill('chat', 'Chat', 'Basic chat capability', ['chat']);

$agentCard = new AgentCard(
    'Streaming Agent',
    'Agent with streaming support',
    'https://example.com/agent',
    '1.0.0',
    $capabilities,
    ['text'],
    ['text'],
    [$skill]
);

echo "Agent supports streaming: " . ($capabilities->isStreaming() ? 'Yes' : 'No') . "\n\n";

// 2. Create event bus and executor
$eventBus = new ExecutionEventBusImpl();
$executor = new DefaultAgentExecutor();

// 3. Create a message and context
$message = Message::createUserMessage('Hello, streaming agent!');
$message->setContextId('ctx-123');
$message->setTaskId('task-456');

$context = new RequestContext(
    $message,
    'task-456',
    'ctx-123'
);

echo "Created message: " . $message->getTextContent() . "\n";
echo "Task ID: " . $context->taskId . "\n";
echo "Context ID: " . $context->contextId . "\n\n";

// 4. Subscribe to events
$events = [];
$eventBus->subscribe('task-456', function($event) use (&$events) {
    $events[] = $event;
    echo "Event received: " . get_class($event) . "\n";
    
    if (method_exists($event, 'toArray')) {
        echo "Event data: " . json_encode($event->toArray(), JSON_PRETTY_PRINT) . "\n\n";
    }
});

// 5. Execute the task
echo "Executing task...\n";
$executor->execute($context, $eventBus);

echo "Total events received: " . count($events) . "\n";
echo "Streaming example completed!\n";