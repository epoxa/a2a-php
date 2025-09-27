<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AProtocol_v0_3_0;
use A2A\Models\v0_3_0\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\v0_3_0\Message;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('basic_agent');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

// Create an agent card
$capabilities = new AgentCapabilities();
$skill = new AgentSkill('basic', 'Basic', 'Basic agent skill', ['basic']);

$agentCard = new AgentCard(
    'Basic Agent',
    'A simple demonstration agent',
    'https://example.com/agent',
    '1.0.0',
    $capabilities,
    ['text'],
    ['text'],
    [$skill]
);

// Initialize the protocol
$protocol = new A2AProtocol_v0_3_0($agentCard, null, $logger);

echo "=== Basic Agent Example ===\n\n";

// Example: Handle a simple request
$sampleRequest = [
    'jsonrpc' => '2.0',
    'method' => 'get_agent_card',
    'id' => 1
];

echo "Handling agent card request...\n";
$response = $protocol->handleRequest($sampleRequest);
echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Example: Create a task
echo "Creating a task...\n";
$task = $protocol->createTask('Example task', ['priority' => 'normal']);
echo "Task created: " . $task->getId() . "\n";
echo "Task description: " . ($task->getMetadata()['description'] ?? 'No description') . "\n";
echo "Task status: " . $task->getStatus()->getState()->value . "\n\n";

// Example: Create a message
echo "Creating a message...\n";
$message = Message::createUserMessage('Hello from Basic Agent!');
echo "Message ID: " . $message->getMessageId() . "\n";
// Get text content from first text part
$textContent = '';
foreach ($message->getParts() as $part) {
    if ($part instanceof \A2A\Models\TextPart) {
        $textContent = $part->getText();
        break;
    }
}
echo "Message content: " . $textContent . "\n";
echo "Message role: " . $message->getRole() . "\n\n";

// Example: Handle ping request
echo "Handling ping request...\n";
$pingRequest = [
    'jsonrpc' => '2.0',
    'method' => 'ping',
    'id' => 2
];
$pingResponse = $protocol->handleRequest($pingRequest);
echo json_encode($pingResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "Basic agent example completed successfully!\n";