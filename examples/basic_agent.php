<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AProtocol;
use A2A\Models\AgentCard;
use A2A\Models\Message;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('basic_agent');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

// Create an agent card
$agentCard = new AgentCard(
    'basic-agent-001',
    'Basic Agent',
    'A simple demonstration agent',
    '1.0.0',
    ['messaging', 'tasks', 'ping'],
    ['environment' => 'example']
);

// Initialize the protocol
$protocol = new A2AProtocol($agentCard, null, $logger);

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
echo "Task description: " . $task->getDescription() . "\n";
echo "Task status: " . $task->getStatus() . "\n\n";

// Example: Create a message
echo "Creating a message...\n";
$message = new Message('Hello from Basic Agent!', 'text');
echo "Message ID: " . $message->getId() . "\n";
echo "Message content: " . $message->getContent() . "\n";
echo "Message type: " . $message->getType() . "\n\n";

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