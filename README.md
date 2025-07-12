# A2A PHP SDK

PHP library implementation of the A2A (AI Agent-to-Agent) Protocol.

https://github.com/a2aproject/A2A


## Features

### Core Protocol Support
-  A2A Protocol v0.2.5 implementation
-  All protocol methods: `message/send`, `message/stream`, `tasks/*`
-  Protocol-compliant AgentCard, Message, Task structures
-  Full Part types: TextPart, FilePart, DataPart
-  JSON-RPC 2.0 with A2A-specific error codes

### Streaming & Real-time
-  Server-Sent Events (SSE) streaming
-  Event-driven architecture with ExecutionEventBus
-  TaskStatusUpdateEvent and TaskArtifactUpdateEvent
-  Concurrent task stream management
-  StreamingClient for SSE consumption

### Advanced Features
-  AgentExecutor framework for complex agent logic
-  RequestContext system for execution state
-  ResultManager for event processing
-  Push notification configuration (CRUD)
-  Task resubscription and reconnection

### Technical Features
-  Modern PHP 8.0+ with strict typing
-  PSR-4 autoloading and PSR-3 logging
-  Comprehensive test coverage
-  HTTP client abstraction with Guzzle

## Installation

```bash
composer require andreibesleaga/a2a-php
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

- `basic_agent.php` - Basic agent implementation
- `client_server.php` - Client-server communication
- `complete_agent_communication.php` - Agent to agent communication
- `streaming_example.php` - Streaming and events
- `advanced_features.php` - All advanced features
- `protocol_compliance.php` - A2A protocol compliance
- `feature_comparison.php` - Feature parity verification
- `task_management.php` - Task lifecycle management

## Streaming Example

```php
use A2A\Events\ExecutionEventBusImpl;
use A2A\Execution\DefaultAgentExecutor;
use A2A\Models\RequestContext;

$eventBus = new ExecutionEventBusImpl();
$executor = new DefaultAgentExecutor();

$message = Message::createUserMessage('Stream me updates!');
$context = new RequestContext($message, 'task-123', 'ctx-123');

// Subscribe to events
$eventBus->subscribe('task-123', function($event) {
    echo "Event: " . get_class($event) . "\n";
});

// Execute with streaming
$executor->execute($context, $eventBus);
```

## Agent Execution Pattern

```php
use A2A\Interfaces\AgentExecutor;
use A2A\Models\TaskStatusUpdateEvent;

class MyAgentExecutor implements AgentExecutor
{
    public function execute(RequestContext $requestContext, ExecutionEventBus $eventBus): void
    {
        // Publish working status
        $workingEvent = new TaskStatusUpdateEvent(
            $requestContext->taskId,
            $requestContext->contextId,
            new TaskStatus(TaskState::WORKING)
        );
        $eventBus->publish($workingEvent);
        
        // Do work...
        
        // Publish completion
        $completedEvent = new TaskStatusUpdateEvent(
            $requestContext->taskId,
            $requestContext->contextId,
            new TaskStatus(TaskState::COMPLETED),
            true // final
        );
        $eventBus->publish($completedEvent);
    }
    
    public function cancelTask(string $taskId, ExecutionEventBus $eventBus): void
    {
        // Handle cancellation
    }
}
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

Apache License - see LICENSE file for details.
