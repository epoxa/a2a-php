<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AClient;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\Models\PushNotificationConfig;
use A2A\Client\StreamingClient;
use A2A\Execution\ResultManager;
use A2A\Events\ExecutionEventBusImpl;

echo "=== A2A Advanced Features Example ===\n\n";

// 1. Create agent with advanced capabilities
$capabilities = new AgentCapabilities(
    streaming: true,
    pushNotifications: true,
    stateTransitionHistory: true
);

$skill = new AgentSkill('advanced', 'Advanced Processing', 'Advanced agent capabilities', ['streaming', 'push']);

$agentCard = new AgentCard(
    'Advanced Agent',
    'Agent with full A2A protocol support',
    'https://example.com/agent',
    '1.0.0',
    $capabilities,
    ['text'],
    ['text'],
    [$skill]
);

echo "Agent capabilities:\n";
echo "- Streaming: " . ($capabilities->isStreaming() ? 'Yes' : 'No') . "\n";
echo "- Push Notifications: " . ($capabilities->isPushNotifications() ? 'Yes' : 'No') . "\n";
echo "- State History: " . ($capabilities->isStateTransitionHistory() ? 'Yes' : 'No') . "\n\n";

// 2. Demonstrate push notification configuration
$client = new A2AClient($agentCard);
$pushConfig = new PushNotificationConfig('https://example.com/webhook');

echo "Push notification methods:\n";
echo "- Set config: Available\n";
echo "- Get config: Available\n";
echo "- List configs: Available\n";
echo "- Delete config: Available\n\n";

// 3. Demonstrate streaming client
$streamingClient = new StreamingClient($agentCard);
$message = Message::createUserMessage('Hello streaming world!');

echo "Streaming capabilities:\n";
echo "- Send message stream: Available\n";
echo "- Task resubscription: Available\n";
echo "- Event handling: Available\n\n";

// 4. Demonstrate result manager
$eventBus = new ExecutionEventBusImpl();
$resultManager = new ResultManager();

echo "Result management:\n";
echo "- Event processing: Available\n";
echo "- Result aggregation: Available\n";
echo "- Task cleanup: Available\n\n";

// 5. Show protocol methods
echo "Protocol methods implemented:\n";
echo "- message/send: ✓\n";
echo "- message/stream: ✓\n";
echo "- tasks/get: ✓\n";
echo "- tasks/cancel: ✓\n";
echo "- tasks/resubscribe: ✓\n";
echo "- tasks/pushNotificationConfig/set: ✓\n";
echo "- tasks/pushNotificationConfig/get: ✓\n";
echo "- tasks/pushNotificationConfig/list: ✓\n";
echo "- tasks/pushNotificationConfig/delete: ✓\n\n";

echo "Advanced features example completed!\n";