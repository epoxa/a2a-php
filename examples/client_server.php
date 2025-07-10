<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AClient;
use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\Message;
use A2A\Utils\HttpClient;

echo "=== Client-Server Communication Example ===\n\n";

// Create server agent
$serverCard = new AgentCard(
    'server-agent-001',
    'Server Agent',
    'Example server agent',
    '1.0.0',
    ['messaging', 'tasks']
);

$server = new A2AServer($serverCard);

// Add message handler to server
$server->addMessageHandler(function($message, $fromAgent) {
    echo "Server received message from {$fromAgent}: {$message->getContent()}\n";
});

// Create client agent
$clientCard = new AgentCard(
    'client-agent-001',
    'Client Agent',
    'Example client agent',
    '1.0.0',
    ['messaging']
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
echo "Remote agent: {$remoteCard->getName()} (ID: {$remoteCard->getId()})\n\n";

// Test send message
echo "Sending message to server...\n";
$message = new Message('Hello from client!', 'text');
$response = $client->sendMessage('http://example.com/api', $message);
echo "Message sent successfully\n\n";

echo "Client-server example completed!\n";