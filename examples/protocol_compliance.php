<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\AgentProvider;
use A2A\Models\Message;
use A2A\Models\Task;
use A2A\Models\TaskState;
use A2A\Models\TextPart;
use A2A\Models\FilePart;
use A2A\Models\DataPart;
use A2A\Models\FileWithBytes;
use A2A\A2AProtocol;
use A2A\A2AClient;
use A2A\A2AServer;

echo "=== A2A Protocol v0.3.0 Compliance Verification ===\n\n";

$passed = 0;
$total = 0;

function test($description, $condition) {
    global $passed, $total;
    $total++;
    $result = $condition ? "✅ PASS" : "❌ FAIL";
    echo "{$result}: {$description}\n";
    if ($condition) $passed++;
    return $condition;
}

// 1. AgentCard Protocol Compliance
echo "1. AgentCard Structure (A2A Protocol v0.3.0):\n";
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
    '0.3.0'
);
$agentCard->setProvider($provider);

$cardArray = $agentCard->toArray();
test("Has required 'name' field", isset($cardArray['name']));
test("Has required 'description' field", isset($cardArray['description']));
test("Has required 'url' field", isset($cardArray['url']));
test("Has required 'version' field", isset($cardArray['version']));
test("Has required 'protocolVersion' field", $cardArray['protocolVersion'] === '0.3.0');
test("Has required 'capabilities' object", isset($cardArray['capabilities']) && is_array($cardArray['capabilities']));
test("Has required 'defaultInputModes' array", isset($cardArray['defaultInputModes']) && is_array($cardArray['defaultInputModes']));
test("Has required 'defaultOutputModes' array", isset($cardArray['defaultOutputModes']) && is_array($cardArray['defaultOutputModes']));
test("Has required 'skills' array", isset($cardArray['skills']) && is_array($cardArray['skills']));
test("Has 'supportsAuthenticatedExtendedCard' boolean", isset($cardArray['supportsAuthenticatedExtendedCard']));
test("Has optional 'provider' object", isset($cardArray['provider']));

// 2. Message Protocol Compliance
echo "\n2. Message Structure (A2A Protocol v0.3.0):\n";
$message = Message::createUserMessage('Hello World');
$message->setContextId('ctx-123');
$message->setTaskId('task-456');
$message->setMetadata(['priority' => 'high']);

$messageArray = $message->toArray();
test("Has required 'kind' field with value 'message'", $messageArray['kind'] === 'message');
test("Has required 'messageId' field", isset($messageArray['messageId']) && !empty($messageArray['messageId']));
test("Has required 'role' field", isset($messageArray['role']));
test("Has required 'parts' array", isset($messageArray['parts']) && is_array($messageArray['parts']));
test("Has optional 'contextId' field", isset($messageArray['contextId']));
test("Has optional 'taskId' field", isset($messageArray['taskId']));
test("Has optional 'metadata' field", isset($messageArray['metadata']));

// 3. Task Protocol Compliance
echo "\n3. Task Structure (A2A Protocol v0.3.0):\n";
$task = new Task('task-123', 'Test task', [], 'ctx-123');
$taskArray = $task->toArray();
test("Has required 'kind' field with value 'task'", $taskArray['kind'] === 'task');
test("Has required 'id' field", isset($taskArray['id']));
test("Has required 'contextId' field", isset($taskArray['contextId']));
test("Has required 'status' object", isset($taskArray['status']) && is_array($taskArray['status']));
test("Status has required 'state' field", isset($taskArray['status']['state']));
test("Status has required 'timestamp' field", isset($taskArray['status']['timestamp']));

// 4. Part Types Protocol Compliance
echo "\n4. Part Types (A2A Protocol v0.3.0):\n";
$textPart = new TextPart('Hello');
$textArray = $textPart->toArray();
test("TextPart has 'kind' field with value 'text'", $textArray['kind'] === 'text');
test("TextPart has 'text' field", isset($textArray['text']));

$filePart = new FilePart(new FileWithBytes('base64data'));
$fileArray = $filePart->toArray();
test("FilePart has 'kind' field with value 'file'", $fileArray['kind'] === 'file');
test("FilePart has 'file' object", isset($fileArray['file']));

$dataPart = new DataPart(['key' => 'value']);
$dataArray = $dataPart->toArray();
test("DataPart has 'kind' field with value 'data'", $dataArray['kind'] === 'data');
test("DataPart has 'data' field", isset($dataArray['data']));

// 5. Protocol Methods Implementation
echo "\n5. A2A Protocol Methods:\n";
$protocol = new A2AProtocol($agentCard);
$server = new A2AServer($agentCard);
$client = new A2AClient($agentCard);

// Test ping method
$pingRequest = ['jsonrpc' => '2.0', 'method' => 'ping', 'id' => 1];
$pingResponse = $protocol->handleRequest($pingRequest);
test("Implements 'ping' method", isset($pingResponse['result']['status']) && $pingResponse['result']['status'] === 'pong');

// Test get_agent_card method
$cardRequest = ['jsonrpc' => '2.0', 'method' => 'get_agent_card', 'id' => 2];
$cardResponse = $protocol->handleRequest($cardRequest);
test("Implements 'get_agent_card' method", isset($cardResponse['result']['name']));

// Test message/send method
$msgRequest = [
    'jsonrpc' => '2.0', 
    'method' => 'message/send', 
    'params' => ['from' => 'test', 'message' => $message->toArray()], 
    'id' => 3
];
$msgResponse = $protocol->handleRequest($msgRequest);
test("Implements 'message/send' method", isset($msgResponse['result']['status']));

// Test tasks methods
test("Has TaskManager for task operations", method_exists($protocol, 'getTaskManager'));
test("Implements task creation", method_exists($protocol, 'createTask'));

// Test client methods
test("Client implements 'ping' method", method_exists($client, 'ping'));
test("Client implements 'getAgentCard' method", method_exists($client, 'getAgentCard'));
test("Client implements 'sendMessage' method", method_exists($client, 'sendMessage'));
test("Client implements 'getTask' method", method_exists($client, 'getTask'));
test("Client implements 'cancelTask' method", method_exists($client, 'cancelTask'));
test("Client implements push notification methods", method_exists($client, 'setPushNotificationConfig'));
test("Client implements 'resubscribeTask' method", method_exists($client, 'resubscribeTask'));

// 6. Advanced Features
echo "\n6. Advanced A2A Features:\n";
test("Streaming capabilities support", class_exists('A2A\\Client\\StreamingClient'));
test("Event system implementation", class_exists('A2A\\Events\\ExecutionEventBusImpl'));
test("Agent execution framework", interface_exists('A2A\\Interfaces\\AgentExecutor'));
test("Request context system", class_exists('A2A\\Models\\RequestContext'));
test("Task status events", class_exists('A2A\\Models\\TaskStatusUpdateEvent'));
test("Task artifact events", class_exists('A2A\\Models\\TaskArtifactUpdateEvent'));
test("Result management", class_exists('A2A\\Execution\\ResultManager'));
test("SSE streaming support", class_exists('A2A\\Streaming\\SSEStreamer'));

// 7. Error Handling
echo "\n7. A2A Error Codes:\n";
test("A2A error codes defined", class_exists('A2A\\Exceptions\\A2AErrorCodes'));
test("Task not found error", defined('A2A\\Exceptions\\A2AErrorCodes::TASK_NOT_FOUND'));
test("Task not cancelable error", defined('A2A\\Exceptions\\A2AErrorCodes::TASK_NOT_CANCELABLE'));
test("Push notification not supported error", defined('A2A\\Exceptions\\A2AErrorCodes::PUSH_NOTIFICATION_NOT_SUPPORTED'));
test("Invalid agent response error", defined('A2A\\Exceptions\\A2AErrorCodes::INVALID_AGENT_RESPONSE'));

// 8. JSON-RPC Compliance
echo "\n8. JSON-RPC 2.0 Compliance:\n";
test("JSON-RPC utility class", class_exists('A2A\\Utils\\JsonRpc'));
$jsonRpc = new \A2A\Utils\JsonRpc();
$request = $jsonRpc->createRequest('test', [], 1);
test("Creates valid JSON-RPC requests", $request['jsonrpc'] === '2.0' && $request['method'] === 'test');
$response = $jsonRpc->createResponse(1, ['status' => 'ok']);
test("Creates valid JSON-RPC responses", $response['jsonrpc'] === '2.0' && isset($response['result']));

echo "\n=== PROTOCOL COMPLIANCE RESULTS ===\n";
echo "Passed: {$passed}/{$total} tests\n";
$percentage = round(($passed / $total) * 100, 1);
echo "Compliance: {$percentage}%\n\n";

if ($percentage >= 95) {
    echo "🎉 EXCELLENT: Full A2A Protocol v0.3.0 compliance achieved!\n";
} elseif ($percentage >= 85) {
    echo "✅ GOOD: Strong A2A Protocol compliance with minor gaps\n";
} elseif ($percentage >= 70) {
    echo "⚠️  PARTIAL: Basic A2A Protocol compliance, improvements needed\n";
} else {
    echo "❌ POOR: Significant A2A Protocol compliance issues\n";
}

echo "\nProtocol compliance verification completed!\n";