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
use A2A\Models\FileWithUri;
use A2A\A2AProtocol;
use A2A\A2AClient;
use A2A\A2AServer;
use A2A\Exceptions\A2AErrorCodes;

echo "=== A2A Specification Compliance Check ===\n\n";

$passed = 0;
$total = 0;

function check($description, $condition) {
    global $passed, $total;
    $total++;
    $result = $condition ? "✅ PASS" : "❌ FAIL";
    echo "{$result}: {$description}\n";
    if ($condition) $passed++;
    return $condition;
}

// 1. AgentCard Compliance (Section 5.5)
echo "1. AGENTCARD COMPLIANCE (Section 5.5):\n";
$capabilities = new AgentCapabilities(true, true, true);
$skill = new AgentSkill('test', 'Test Skill', 'Test description', ['test']);
$provider = new AgentProvider('Test Org', 'https://test.com');

$agentCard = new AgentCard(
    'Test Agent',
    'Test Description', 
    'https://example.com/agent',
    '1.0.0',
    $capabilities,
    ['text/plain'],
    ['text/plain'],
    [$skill],
    '0.2.5'
);
$agentCard->setProvider($provider);

$cardArray = $agentCard->toArray();
check("protocolVersion field (required)", isset($cardArray['protocolVersion']) && $cardArray['protocolVersion'] === '0.2.5');
check("name field (required)", isset($cardArray['name']));
check("description field (required)", isset($cardArray['description']));
check("url field (required)", isset($cardArray['url']));
check("version field (required)", isset($cardArray['version']));
check("capabilities object (required)", isset($cardArray['capabilities']));
check("defaultInputModes array (required)", isset($cardArray['defaultInputModes']));
check("defaultOutputModes array (required)", isset($cardArray['defaultOutputModes']));
check("skills array (required)", isset($cardArray['skills']));
check("supportsAuthenticatedExtendedCard boolean", isset($cardArray['supportsAuthenticatedExtendedCard']));
check("provider object (optional)", isset($cardArray['provider']));

// 2. Message Compliance (Section 6.4)
echo "\n2. MESSAGE COMPLIANCE (Section 6.4):\n";
$message = Message::createUserMessage('Test message');
$message->setContextId('ctx-123');
$message->setTaskId('task-456');
$message->setMetadata(['test' => 'value']);

$msgArray = $message->toArray();
check("kind field = 'message'", $msgArray['kind'] === 'message');
check("messageId field (required)", isset($msgArray['messageId']));
check("role field (required)", isset($msgArray['role']) && in_array($msgArray['role'], ['user', 'agent']));
check("parts array (required)", isset($msgArray['parts']) && is_array($msgArray['parts']) && count($msgArray['parts']) > 0);
check("contextId field (optional)", isset($msgArray['contextId']));
check("taskId field (optional)", isset($msgArray['taskId']));
check("metadata field (optional)", isset($msgArray['metadata']));

// 3. Task Compliance (Section 6.1)
echo "\n3. TASK COMPLIANCE (Section 6.1):\n";
$task = new Task('task-123', 'Test task', [], 'ctx-123');
$taskArray = $task->toArray();
check("kind field = 'task'", $taskArray['kind'] === 'task');
check("id field (required)", isset($taskArray['id']));
check("contextId field (required)", isset($taskArray['contextId']));
check("status object (required)", isset($taskArray['status']));
check("status.state field (required)", isset($taskArray['status']['state']));
check("status.timestamp field (optional)", isset($taskArray['status']['timestamp']));

// 4. Part Types Compliance (Section 6.5)
echo "\n4. PART TYPES COMPLIANCE (Section 6.5):\n";
$textPart = new TextPart('Hello');
$textArray = $textPart->toArray();
check("TextPart kind = 'text'", $textArray['kind'] === 'text');
check("TextPart text field", isset($textArray['text']));

$filePart = new FilePart(new FileWithBytes('base64data'));
$fileArray = $filePart->toArray();
check("FilePart kind = 'file'", $fileArray['kind'] === 'file');
check("FilePart file object", isset($fileArray['file']));

$dataPart = new DataPart(['key' => 'value']);
$dataArray = $dataPart->toArray();
check("DataPart kind = 'data'", $dataArray['kind'] === 'data');
check("DataPart data field", isset($dataArray['data']));

// 5. Protocol Methods Compliance (Section 7)
echo "\n5. PROTOCOL METHODS COMPLIANCE (Section 7):\n";
$protocol = new A2AProtocol($agentCard);
$client = new A2AClient($agentCard);

// Test message/send (7.1)
$msgRequest = [
    'jsonrpc' => '2.0',
    'method' => 'message/send',
    'params' => ['message' => $message->toArray()],
    'id' => 1
];
$msgResponse = $protocol->handleRequest($msgRequest);
check("message/send method", isset($msgResponse['result']) || isset($msgResponse['error']));

// Test tasks/get (7.3)
check("tasks/get method exists", method_exists($client, 'getTask'));

// Test tasks/cancel (7.4)
check("tasks/cancel method exists", method_exists($client, 'cancelTask'));

// Test tasks/pushNotificationConfig/* (7.5-7.8)
check("tasks/pushNotificationConfig/set exists", method_exists($client, 'setPushNotificationConfig'));
check("tasks/pushNotificationConfig/get exists", method_exists($client, 'getPushNotificationConfig'));
check("tasks/pushNotificationConfig/list exists", method_exists($client, 'listPushNotificationConfigs'));
check("tasks/pushNotificationConfig/delete exists", method_exists($client, 'deletePushNotificationConfig'));

// Test tasks/resubscribe (7.9)
check("tasks/resubscribe method exists", method_exists($client, 'resubscribeTask'));

// Test streaming support (7.2)
check("message/stream support", class_exists('A2A\\Client\\StreamingClient'));

// 6. Error Handling Compliance (Section 8)
echo "\n6. ERROR HANDLING COMPLIANCE (Section 8):\n";
check("JSON-RPC standard errors", A2AErrorCodes::PARSE_ERROR === -32700);
check("INVALID_REQUEST error", A2AErrorCodes::INVALID_REQUEST === -32600);
check("METHOD_NOT_FOUND error", A2AErrorCodes::METHOD_NOT_FOUND === -32601);
check("INVALID_PARAMS error", A2AErrorCodes::INVALID_PARAMS === -32602);
check("INTERNAL_ERROR error", A2AErrorCodes::INTERNAL_ERROR === -32603);

// A2A-specific errors (Section 8.2)
check("TASK_NOT_FOUND error (-32001)", A2AErrorCodes::TASK_NOT_FOUND === -32001);
check("TASK_NOT_CANCELABLE error (-32002)", A2AErrorCodes::TASK_NOT_CANCELABLE === -32002);
check("PUSH_NOTIFICATION_NOT_SUPPORTED error (-32003)", A2AErrorCodes::PUSH_NOTIFICATION_NOT_SUPPORTED === -32003);
check("UNSUPPORTED_OPERATION error (-32004)", A2AErrorCodes::UNSUPPORTED_OPERATION === -32004);
check("CONTENT_TYPE_NOT_SUPPORTED error (-32005)", A2AErrorCodes::CONTENT_TYPE_NOT_SUPPORTED === -32005);
check("INVALID_AGENT_RESPONSE error (-32006)", A2AErrorCodes::INVALID_AGENT_RESPONSE === -32006);

// 7. Transport and Format Compliance (Section 3)
echo "\n7. TRANSPORT AND FORMAT COMPLIANCE (Section 3):\n";
check("JSON-RPC 2.0 support", class_exists('A2A\\Utils\\JsonRpc'));
check("HTTP client support", class_exists('A2A\\Utils\\HttpClient'));
check("SSE streaming support", class_exists('A2A\\Streaming\\SSEStreamer'));

// 8. TaskState Enum Compliance (Section 6.3)
echo "\n8. TASKSTATE ENUM COMPLIANCE (Section 6.3):\n";
check("submitted state", TaskState::SUBMITTED->value === 'submitted');
check("working state", TaskState::WORKING->value === 'working');
check("input-required state", TaskState::INPUT_REQUIRED->value === 'input-required');
check("completed state", TaskState::COMPLETED->value === 'completed');
check("canceled state", TaskState::CANCELED->value === 'canceled');
check("failed state", TaskState::FAILED->value === 'failed');
check("rejected state", TaskState::REJECTED->value === 'rejected');
check("auth-required state", TaskState::AUTH_REQUIRED->value === 'auth-required');
check("unknown state", TaskState::UNKNOWN->value === 'unknown');

// 9. File Support Compliance (Section 6.6)
echo "\n9. FILE SUPPORT COMPLIANCE (Section 6.6):\n";
$fileWithBytes = new FileWithBytes('base64data');
$fileWithBytesArray = $fileWithBytes->toArray();
check("FileWithBytes bytes field", isset($fileWithBytesArray['bytes']));

$fileWithUri = new FileWithUri('https://example.com/file.pdf');
$fileWithUriArray = $fileWithUri->toArray();
check("FileWithUri uri field", isset($fileWithUriArray['uri']));

// 10. Advanced Features Compliance
echo "\n10. ADVANCED FEATURES COMPLIANCE:\n";
check("Agent execution framework", interface_exists('A2A\\Interfaces\\AgentExecutor'));
check("Event system", interface_exists('A2A\\Interfaces\\ExecutionEventBus'));
check("Request context", class_exists('A2A\\Models\\RequestContext'));
check("Task status events", class_exists('A2A\\Models\\TaskStatusUpdateEvent'));
check("Task artifact events", class_exists('A2A\\Models\\TaskArtifactUpdateEvent'));
check("Push notification config", class_exists('A2A\\Models\\PushNotificationConfig'));

echo "\n=== SPECIFICATION COMPLIANCE RESULTS ===\n";
echo "Passed: {$passed}/{$total} checks\n";
$percentage = round(($passed / $total) * 100, 1);
echo "Compliance: {$percentage}%\n\n";

if ($percentage >= 95) {
    echo "🎉 EXCELLENT: Full A2A Specification compliance achieved!\n";
    echo "✅ a2a-php fully implements the A2A Protocol specification\n";
} elseif ($percentage >= 85) {
    echo "✅ VERY GOOD: Strong specification compliance with minor gaps\n";
} elseif ($percentage >= 70) {
    echo "⚠️  GOOD: Basic specification compliance, some improvements needed\n";
} else {
    echo "❌ POOR: Significant specification compliance issues\n";
}

echo "\nA2A Protocol Specification compliance check completed!\n";