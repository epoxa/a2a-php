# A2A PHP SDK

A PHP implementation of the A2A (Agent-to-Agent) Protocol, providing a SDK for building PHP AI agent-based applications.
https://a2aproject.github.io/A2A/latest/

## Features

-  A2A Protocol implementation
-  Modern PHP 8.0+ with strict typing
-  PSR-4 autoloading
-  Test coverage
-  JSON-RPC 2.0 support
-  HTTP client abstraction
-  Logging

## Installation

```bash
composer require a2a/a2a-php
```

## Quick Start

### Creating an Agent

```php
use A2A\A2AProtocol;
use A2A\Models\AgentCard;

$agentCard = new AgentCard(
    'my-agent-001',
    'My Agent',
    'A sample agent implementation',
    '1.0.0',
    ['messaging', 'tasks'],
    ['environment' => 'production']
);

$protocol = new A2AProtocol($agentCard);
```

### Using A2A Client

```php
use A2A\A2AClient;
use A2A\Models\AgentCard;
use A2A\Models\Message;

$agentCard = new AgentCard('client-agent', 'Client Agent');
$client = new A2AClient($agentCard);

// Send a message
$message = new Message('Hello, World!', 'text');
$response = $client->sendMessage('http://other-agent.com/api', $message);

// Ping another agent
$isAlive = $client->ping('http://other-agent.com/api');

// Get agent card
$remoteCard = $client->getAgentCard('http://other-agent.com/api');
```

### Using A2A Server

```php
use A2A\A2AServer;
use A2A\Models\AgentCard;

$agentCard = new AgentCard('server-agent', 'Server Agent');
$server = new A2AServer($agentCard);

// Add message handler
$server->addMessageHandler(function($message, $fromAgent) {
    echo "Received message from {$fromAgent}: {$message->getContent()}\n";
});

// Handle incoming request
$request = json_decode(file_get_contents('php://input'), true);
$response = $server->handleRequest($request);

header('Content-Type: application/json');
echo json_encode($response);
```

### Creating Tasks

```php
$task = $protocol->createTask('Process user data', ['userId' => 123]);
echo $task->getId(); // UUID of the created task

// Update task status
$task->setStatus('in_progress');
$task->assignTo('worker-agent-001');
```

## API Reference

### AgentCard

Represents an agent's identity and capabilities:

```php
$card = new AgentCard(
    string $id,
    string $name,
    string $description = '',
    string $version = '1.0.0',
    array $capabilities = [],
    array $metadata = []
);

// Methods
$card->addCapability('new_capability');
$card->setMetadata('key', 'value');
$hasCapability = $card->hasCapability('messaging');
```

### Message

Represents a message between agents:

```php
$message = new Message(
    string $content,
    string $type = 'text',
    ?string $id = null,
    array $metadata = []
);

// Add parts to message
$part = new Part('attachment', 'file.pdf');
$message->addPart($part);
```

### Task

Represents a task with context and status:

```php
$task = new Task(
    string $id,
    string $description,
    array $context = [],
    string $status = 'pending'
);

// Methods
$task->setStatus('completed');
$task->assignTo('agent-id');
$isCompleted = $task->isCompleted();
```

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Check code style:

```bash
composer style
```

## Protocol Methods

The SDK supports the following A2A protocol methods:

- `send_message` - Send a message to an agent
- `get_agent_card` - Retrieve agent information
- `ping` - Health check endpoint

## Error Handling

The SDK provides specific exceptions:

- `A2AException` - Base exception class
- `InvalidRequestException` - Invalid JSON-RPC requests
- `TaskNotFoundException` - Task not found errors

## Logging

The SDK supports PSR-3 logging. Pass a logger instance to the constructor:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('a2a');
$logger->pushHandler(new StreamHandler('a2a.log', Logger::INFO));

$protocol = new A2AProtocol($agentCard, null, $logger);
```

## Examples

See the `examples/` directory for complete working examples:

- Basic agent implementation
- Client-server communication
- Task management
- Message handling

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

Apache License - see LICENSE file for details.
