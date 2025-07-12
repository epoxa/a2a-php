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
// Set config
$taskId = 'demo-task-001';
$setResult = $client->setPushNotificationConfig($taskId, $pushConfig);
echo "- Set config: " . ($setResult ? 'SUCCESS' : 'FAILED') . "\n";

// Get config
$getResult = $client->getPushNotificationConfig($taskId);
echo "- Get config: " . ($getResult ? 'SUCCESS' : 'FAILED') . "\n";

// List configs
$listResult = $client->listPushNotificationConfigs();
echo "- List configs: Found " . count($listResult) . " configs\n";

// Delete config
$deleteResult = $client->deletePushNotificationConfig($taskId);
echo "- Delete config: " . ($deleteResult ? 'SUCCESS' : 'FAILED') . "\n\n";

// 3. Demonstrate streaming client
$streamingClient = new StreamingClient($agentCard);
$message = Message::createUserMessage('Hello streaming world!');

echo "Streaming capabilities:\n";
// Send message stream
try {
    $streamingClient->sendMessageStream('https://example.com/agent', $message, function($event) {
        // Handle streaming events
    });
    echo "- Send message stream: SUCCESS\n";
} catch (\Exception $e) {
    echo "- Send message stream: FAILED - " . $e->getMessage() . "\n";
}

// Task resubscription
try {
    $streamingClient->resubscribeTask('https://example.com/agent', 'stream-task-001', function($event) {
        // Handle resubscription events
    });
    echo "- Task resubscription: SUCCESS\n";
} catch (\Exception $e) {
    echo "- Task resubscription: FAILED - " . $e->getMessage() . "\n";
}

// Event handling
$eventCount = 0;
echo "- Event handling: Registered (" . $eventCount . " events processed)\n\n";

// 4. Demonstrate result manager
$eventBus = new ExecutionEventBusImpl();
$resultManager = new ResultManager();

echo "Result management:\n";
// Event processing
$taskId = 'result-task-001';
$eventBus->publish($taskId, ['type' => 'task_started', 'timestamp' => time()]);
$processedEvents = $resultManager->processEvents($taskId);
echo "- Event processing: Processed " . count($processedEvents) . " events\n";

// Result aggregation
$results = [
    ['status' => 'completed', 'data' => 'result1'],
    ['status' => 'completed', 'data' => 'result2']
];
$aggregatedResult = $resultManager->aggregateResults($results);
echo "- Result aggregation: Aggregated " . count($results) . " results\n";

// Task cleanup
$cleanupResult = $resultManager->cleanupTask($taskId);
echo "- Task cleanup: " . ($cleanupResult ? 'SUCCESS' : 'FAILED') . "\n\n";

// 5. Show protocol methods
echo "Protocol methods implemented:\n";
// Test message/send
$testMessage = Message::createUserMessage('Test message');
$sendResult = $client->sendMessage('https://example.com/agent', $testMessage);
echo "- message/send: " . ($sendResult ? '✓ SUCCESS' : '✗ FAILED') . "\n";

// Test message/stream
try {
    $streamingClient->sendMessageStream('https://example.com/agent', $testMessage, function($event) {});
    echo "- message/stream: ✓ SUCCESS\n";
} catch (\Exception $e) {
    echo "- message/stream: ✗ FAILED\n";
}

// Test tasks/get
$taskId = 'test-task-001';
$getTaskResult = $client->getTask($taskId);
echo "- tasks/get: " . ($getTaskResult !== null ? '✓ SUCCESS' : '✗ FAILED') . "\n";

// Test tasks/cancel
$cancelResult = $client->cancelTask($taskId);
echo "- tasks/cancel: " . ($cancelResult ? '✓ SUCCESS' : '✗ FAILED') . "\n";

// Test tasks/resubscribe
$resubResult = $client->resubscribeTask($taskId);
echo "- tasks/resubscribe: " . ($resubResult ? '✓ SUCCESS' : '✗ FAILED') . "\n";

// Test push notification config methods
$configTaskId = 'test-config-task-001';
$testPushConfig = new PushNotificationConfig('https://test.example.com/webhook', 'test-config-001');

$setPushResult = $client->setPushNotificationConfig($configTaskId, $testPushConfig);
echo "- tasks/pushNotificationConfig/set: " . ($setPushResult ? '✓ SUCCESS' : '✗ FAILED') . "\n";

$getPushResult = $client->getPushNotificationConfig($configTaskId);
echo "- tasks/pushNotificationConfig/get: " . ($getPushResult ? '✓ SUCCESS' : '✗ FAILED') . "\n";

$listPushResult = $client->listPushNotificationConfigs();
echo "- tasks/pushNotificationConfig/list: ✓ Found " . count($listPushResult) . " configs\n";

$deletePushResult = $client->deletePushNotificationConfig($configTaskId);
echo "- tasks/pushNotificationConfig/delete: " . ($deletePushResult ? '✓ SUCCESS' : '✗ FAILED') . "\n\n";

echo "Advanced features example completed!\n";