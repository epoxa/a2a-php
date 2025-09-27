<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AClient;
use A2A\A2AServer;
use A2A\Models\v0_3_0\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\v0_3_0\Message;
use A2A\Utils\HttpClient;

echo "=== Client-Server Communication Example ===\n\n";

// Create server agent
$capabilities = new AgentCapabilities();
$skill = new AgentSkill('messaging', 'Messaging', 'Message handling', ['messaging']);

$serverCard = new AgentCard(
    'Server Agent',
    'Example server agent',
    'https://example.com/server',
    '1.0.0',
    $capabilities,
    ['text'],
    ['text'],
    [$skill]
);

$protocol = new \A2A\A2AProtocol_v0_3_0($serverCard);
$server = new A2AServer($protocol);

// Add message handler to server
$messageHandler = new class implements \A2A\Interfaces\MessageHandlerInterface {
    public function canHandle(Message $message): bool {
        return true;
    }
    
    public function handle(Message $message, string $fromAgent): array {
        // Get text content from first text part
        $textContent = '';
        foreach ($message->getParts() as $part) {
            if ($part instanceof \A2A\Models\TextPart) {
                $textContent = $part->getText();
                break;
            }
        }
        echo "Server received message from {$fromAgent}: {$textContent}\n";
        return ['status' => 'received'];
    }
};
$server->addMessageHandler($messageHandler);

// Create client agent
$clientCard = new AgentCard(
    'Client Agent',
    'Example client agent',
    'https://example.com/client',
    '1.0.0',
    $capabilities,
    ['text'],
    ['text'],
    [$skill]
);

// Mock HTTP client for demonstration
$httpClient = new class extends HttpClient {
    private A2AServer $server;
    
    public function setServer(A2AServer $server): void {
        $this->server = $server;
    }
    
    public function post(string $url, array $data): array {
        // Simulate server handling the request
        return $this->server->handleRequest($data);
    }
};

$httpClient->setServer($server);
$client = new A2AClient($clientCard, $httpClient);

// Test ping
echo "Testing ping...\n";
$isAlive = $client->ping('http://example.com/api');
echo "Server is " . ($isAlive ? "alive" : "not responding") . "\n\n";

// Test get agent card
echo "Getting server agent card...\n";
$remoteCard = $client->getAgentCard('http://example.com/api');
echo "Remote agent: {$remoteCard->getName()}\n\n";

// Test send message
echo "Sending message to server...\n";
$message = Message::createUserMessage('Hello from client!');
$response = $client->sendMessage('http://example.com/api', $message);
echo "Message sent successfully\n\n";

echo "Client-server example completed!\n";