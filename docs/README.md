# A2A PHP Documentation

## Overview

A2A PHP is a complete implementation of the A2A Protocol v0.3.0, providing a robust foundation for building agent-to-agent communication systems in PHP.

## Features

### Core Protocol Support
- **JSON-RPC 2.0 Transport**: Full compliance with JSON-RPC 2.0 specification
- **Agent Cards**: Comprehensive agent capability description and discovery
- **Message Handling**: Support for text, file, and structured data messages
- **Task Management**: Asynchronous task creation, monitoring, and lifecycle management
- **Security**: Multiple authentication schemes (OAuth2, API Key, Mutual TLS)

### Advanced Features
- **Streaming Support**: Real-time message streaming with SSE
- **Push Notifications**: WebSocket and HTTP callback support
- **File Handling**: Inline bytes and URI-based file attachments
- **Error Handling**: Comprehensive error codes and exception handling
- **HTTPS/TLS**: Production-ready security implementation

## Architecture

### Core Components

```
A2A PHP Architecture
├── A2AServer          # Main server implementation
├── A2AClient          # Client for agent communication
├── A2AProtocol        # Protocol message handling
├── TaskManager        # Task lifecycle management
├── Models/            # Data models and structures
│   ├── AgentCard      # Agent capability description
│   ├── Message        # Communication messages
│   ├── Task           # Asynchronous tasks
│   └── Parts/         # Message content types
├── Security/          # Authentication schemes
├── Storage/           # Persistence layer
├── Streaming/         # Real-time communication
└── Utils/             # HTTP client and JSON-RPC utilities
```

### Protocol Compliance

The implementation provides 100% compliance with A2A Protocol v0.3.0:

- ✅ **Agent Discovery**: `getAgentCard` method with full capability description
- ✅ **Health Checks**: `ping` method for service availability
- ✅ **Message Exchange**: `sendMessage` with multi-part content support
- ✅ **Task Management**: `tasks/send` and `tasks/get` for asynchronous operations
- ✅ **Security**: Multiple authentication schemes and HTTPS support
- ✅ **Error Handling**: Standard error codes and detailed error responses

## Installation

### Requirements
- PHP 8.0 or higher
- ext-json
- ext-curl
- Composer

### Install via Composer
```bash
composer require a2a-protocol/a2a-php
```

### Development Setup
```bash
git clone https://github.com/a2a-protocol/a2a-php.git
cd a2a-php
composer install
composer test
```

## Quick Start

### Creating an Agent Server

```php
<?php
use A2A\A2AServer;
use A2A\Models\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;

// Define agent capabilities
$capabilities = new AgentCapabilities(
    canReceiveMessages: true,
    canSendMessages: true,
    canManageTasks: true,
    supportsStreaming: true
);

// Define agent skills
$skills = [
    new AgentSkill(
        id: 'text_analysis',
        name: 'Text Analysis',
        description: 'Analyze and process text content',
        inputModes: ['text/plain', 'text/markdown'],
        outputModes: ['text/plain', 'application/json']
    )
];

// Create agent card
$agentCard = new AgentCard(
    name: 'Text Analysis Agent',
    description: 'An AI agent specialized in text analysis and processing',
    url: 'https://my-agent.example.com',
    version: '1.0.0',
    capabilities: $capabilities,
    defaultInputModes: ['text/plain'],
    defaultOutputModes: ['text/plain'],
    skills: $skills
);

// Start the server
$server = new A2AServer($agentCard);
$server->start();
```

### Connecting to an Agent

```php
<?php
use A2A\A2AClient;
use A2A\Models\Message;
use A2A\Models\TextPart;

// Create client
$client = new A2AClient('https://agent.example.com');

// Get agent information
$agentCard = $client->getAgentCard();
echo "Connected to: " . $agentCard->getName() . "\n";
echo "Capabilities: " . json_encode($agentCard->getCapabilities()->toArray()) . "\n";

// Send a message
$message = new Message(
    role: 'user',
    parts: [new TextPart('Please analyze this text for sentiment.')]
);

$response = $client->sendMessage($message);
foreach ($response->getParts() as $part) {
    if ($part instanceof TextPart) {
        echo "Response: " . $part->getText() . "\n";
    }
}
```

### Task Management

```php
<?php
use A2A\TaskManager;
use A2A\Models\Message;
use A2A\Models\TextPart;

$taskManager = new TaskManager();

// Create a long-running task
$message = new Message('user', [
    new TextPart('Process this large document and extract key insights.')
]);

$task = $taskManager->createTask($message);
echo "Task created: " . $task->getId() . "\n";

// Monitor task progress
do {
    sleep(1);
    $status = $taskManager->getTask($task->getId());
    echo "Status: " . $status->getStatus()->value . "\n";
} while ($status->getStatus() === TaskStatus::RUNNING);

// Get results
if ($status->getStatus() === TaskStatus::COMPLETED) {
    $result = $status->getResult();
    echo "Task completed successfully\n";
}
```

## API Reference

### Core Classes

#### A2AServer
Main server implementation for hosting A2A agents.

**Methods:**
- `__construct(AgentCard $agentCard, ?LoggerInterface $logger = null)`
- `addMessageHandler(MessageHandlerInterface $handler): void`
- `start(): void`
- `handleRequest(string $input): string`

#### A2AClient
Client for connecting to and communicating with A2A agents.

**Methods:**
- `__construct(string $baseUrl, ?HttpClient $httpClient = null)`
- `getAgentCard(): AgentCard`
- `ping(): array`
- `sendMessage(Message $message): Message`
- `sendTask(Message $message): string`
- `getTask(string $taskId): Task`

#### AgentCard
Describes agent capabilities and metadata.

**Properties:**
- `name: string` - Agent name
- `description: string` - Agent description
- `url: string` - Agent endpoint URL
- `version: string` - Agent version
- `protocolVersion: string` - A2A protocol version
- `capabilities: AgentCapabilities` - Agent capabilities
- `skills: AgentSkill[]` - Available skills

### Message Types

#### TextPart
Plain text message content.

```php
$textPart = new TextPart('Hello, world!');
```

#### FilePart
File attachment with metadata.

```php
$filePart = new FilePart(
    name: 'document.pdf',
    mimeType: 'application/pdf',
    file: new FileWithBytes($fileData)
);
```

#### DataPart
Structured data content.

```php
$dataPart = new DataPart([
    'type' => 'analysis_result',
    'confidence' => 0.95,
    'categories' => ['positive', 'business']
]);
```

### Security

The library supports multiple authentication schemes:

#### API Key Authentication
```php
$securityScheme = new APIKeySecurityScheme(
    name: 'X-API-Key',
    in: 'header'
);
```

#### OAuth2 Authentication
```php
$securityScheme = new OAuth2SecurityScheme(
    flows: [
        'clientCredentials' => [
            'tokenUrl' => 'https://auth.example.com/token',
            'scopes' => ['agent:read', 'agent:write']
        ]
    ]
);
```

#### HTTP Authentication
```php
$securityScheme = new HTTPAuthSecurityScheme(
    scheme: 'bearer',
    bearerFormat: 'JWT'
);
```

## Testing

The library includes comprehensive test coverage:

- **135 tests** covering all components
- **414 assertions** validating functionality
- **Unit tests** for individual components
- **Integration tests** for component interaction
- **End-to-end tests** for complete scenarios
- **Performance tests** for high-load scenarios

Run tests:
```bash
composer test
```

Generate coverage report:
```bash
composer test -- --coverage-html coverage
```

## Examples

See the `examples/` directory for complete working examples:

- `basic_agent.php` - Simple agent implementation
- `client_server.php` - Client-server communication
- `task_management.php` - Asynchronous task handling
- `streaming_example.php` - Real-time streaming
- `security_example.php` - Authentication implementation

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

MIT License. See LICENSE file for details.

## Support

- GitHub Issues: https://github.com/a2a-protocol/a2a-php/issues
- Documentation: https://a2a-protocol.github.io/a2a-php/
- A2A Protocol Specification: https://a2a-protocol.org/