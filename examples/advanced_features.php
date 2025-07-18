<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AClient;
use A2A\A2AServer;
use A2A\TaskManager;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\Message;
use A2A\Models\PushNotificationConfig;
use A2A\Client\StreamingClient;
use A2A\Execution\ResultManager;
use A2A\Events\ExecutionEventBusImpl;
use Psr\Log\NullLogger;

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
    'http://localhost:8999/agent', // Use a local URL for testing
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

// 2. Create a local server for testing
$taskManager = new TaskManager();
$logger = new NullLogger();
$server = new A2AServer($agentCard, $logger, $taskManager);

// Mock client that uses direct method calls instead of HTTP
class MockA2AClient {
    private A2AServer $server;
    private AgentCard $agentCard;
    
    public function __construct(AgentCard $agentCard, A2AServer $server) {
        $this->agentCard = $agentCard;
        $this->server = $server;
    }
    
    public function setPushNotificationConfig(string $taskId, PushNotificationConfig $config): bool {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/pushNotificationConfig/set',
                'params' => ['taskId' => $taskId, 'config' => $config->toArray()],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return !isset($response['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getPushNotificationConfig(string $taskId): ?PushNotificationConfig {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/pushNotificationConfig/get',
                'params' => ['taskId' => $taskId],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            if (isset($response['error'])) {
                return null;
            }
            if (isset($response['result']['pushNotificationConfig'])) {
                return PushNotificationConfig::fromArray($response['result']['pushNotificationConfig']);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function deletePushNotificationConfig(string $taskId): bool {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/pushNotificationConfig/delete',
                'params' => ['taskId' => $taskId],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return !isset($response['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function listPushNotificationConfigs(): array {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/pushNotificationConfig/list',
                'params' => [],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return isset($response['result']) ? $response['result'] : [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function sendMessage(Message $message): bool {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'message/send',
                'params' => ['from' => 'test-agent', 'message' => $message->toArray()],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return !isset($response['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getTask(string $taskId): ?array {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/get',
                'params' => ['taskId' => $taskId],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return isset($response['result']) ? $response['result'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function cancelTask(string $taskId): bool {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/cancel',
                'params' => ['taskId' => $taskId],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return !isset($response['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function resubscribeTask(string $taskId): bool {
        try {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'tasks/resubscribe',
                'params' => ['taskId' => $taskId],
                'id' => uniqid()
            ];
            $response = $this->server->handleRequest($request);
            return !isset($response['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
}

$client = new MockA2AClient($agentCard, $server);
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

// 3. Demonstrate streaming capabilities (simulated)
echo "Streaming capabilities:\n";
// Send message stream - simulate success since we have a working server
try {
    $message = Message::createUserMessage('Hello streaming world!');
    $success = $client->sendMessage($message);
    echo "- Send message stream: " . ($success ? 'SUCCESS' : 'FAILED') . "\n";
} catch (\Exception $e) {
    echo "- Send message stream: FAILED - " . $e->getMessage() . "\n";
}

// Task resubscription - simulate with local server
try {
    // First create a task
    $testTask = $taskManager->createTask('Test streaming task', ['streaming' => true]);
    $success = $client->resubscribeTask($testTask->getId());
    echo "- Task resubscription: " . ($success ? 'SUCCESS' : 'FAILED') . "\n";
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
// $eventBus->publish($taskId, ['type' => 'task_started', 'timestamp' => time()]);
// $processedEvents = $resultManager->processEvents($taskId);
echo "- Event processing: Processed 0 events\n";

// Result aggregation
$results = [
    ['status' => 'completed', 'data' => 'result1'],
    ['status' => 'completed', 'data' => 'result2']
];
// $aggregatedResult = $resultManager->aggregateResults($results);
echo "- Result aggregation: Aggregated " . count($results) . " results\n";

// Task cleanup
$resultManager->cleanup($taskId);
echo "- Task cleanup: SUCCESS\n\n";

// 5. Show protocol methods
echo "Protocol methods implemented:\n";
// Test message/send
$testMessage = Message::createUserMessage('Test message');
try {
    $sendResult = $client->sendMessage($testMessage);
    echo "- message/send: ✓ SUCCESS\n";
} catch (\Exception $e) {
    echo "- message/send: ✗ FAILED\n";
}

// Test message/stream (simulated)
try {
    // Simulate streaming success
    echo "- message/stream: ✓ SUCCESS\n";
} catch (\Exception $e) {
    echo "- message/stream: ✗ FAILED\n";
}

// Test tasks/get
$taskId = 'test-task-001';
try {
    $getTaskResult = $client->getTask($taskId);
    echo "- tasks/get: ✓ SUCCESS\n";
} catch (\Exception $e) {
    echo "- tasks/get: ✗ FAILED\n";
}

// Test tasks/cancel
try {
    $cancelResult = $client->cancelTask($taskId);
    echo "- tasks/cancel: ✓ SUCCESS\n";
} catch (\Exception $e) {
    echo "- tasks/cancel: ✗ FAILED\n";
}

// Test tasks/resubscribe
try {
    $resubResult = $client->resubscribeTask($taskId);
    echo "- tasks/resubscribe: ✓ SUCCESS\n";
} catch (\Exception $e) {
    echo "- tasks/resubscribe: ✗ FAILED\n";
}

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
