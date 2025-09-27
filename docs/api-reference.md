# A2A PHP API Reference

## Table of Contents

1. [Core Classes](#core-classes)
2. [Models](#models)
3. [Security](#security)
4. [Utilities](#utilities)
5. [Exceptions](#exceptions)
6. [Interfaces](#interfaces)

## Core Classes

### A2AServer

Main server implementation for hosting A2A agents.

```php
class A2AServer
{
    public function __construct(
        AgentCard $agentCard,
        ?LoggerInterface $logger = null
    );
    
    public function addMessageHandler(MessageHandlerInterface $handler): void;
    public function start(): void;
    public function handleRequest(string $input): string;
    public function setTaskManager(TaskManager $taskManager): void;
    public function setPushNotificationManager(PushNotificationManager $manager): void;
}
```

**Example Usage:**
```php
$agentCard = new AgentCard(/* ... */);
$server = new A2AServer($agentCard);
$server->addMessageHandler(new EchoMessageHandler());
$server->start();
```

### A2AClient

Client for connecting to and communicating with A2A agents.

```php
class A2AClient
{
    public function __construct(
        string $baseUrl,
        ?HttpClient $httpClient = null
    );
    
    public function getAgentCard(): AgentCard;
    public function ping(): array;
    public function sendMessage(Message $message): Message;
    public function sendTask(Message $message): string;
    public function getTask(string $taskId): Task;
}
```

**Example Usage:**
```php
$client = new A2AClient('https://agent.example.com');
$agentCard = $client->getAgentCard();
$response = $client->sendMessage($message);
```

### A2AProtocol

Core protocol handler for processing A2A requests.

```php
class A2AProtocol
{
    public function __construct(
        AgentCard $agentCard,
        ?LoggerInterface $logger = null
    );
    
    public function handleRequest(array $request): array;
    public function getAgentCard(): AgentCard;
    public function createTask(Message $message): Task;
    public function addMessageHandler(MessageHandlerInterface $handler): void;
}
```

### TaskManager

Manages task lifecycle and persistence.

```php
class TaskManager
{
    public function __construct(?Storage $storage = null);
    
    public function createTask(Message $message): Task;
    public function getTask(string $taskId): Task;
    public function updateTask(string $taskId, TaskStatus $status, $result = null): Task;
    public function cancelTask(string $taskId): Task;
    public function listTasks(): array;
}
```

## Models

### AgentCard

Describes agent capabilities and metadata.

```php
class AgentCard
{
    public function __construct(
        string $name,
        string $description,
        string $url,
        string $version,
        AgentCapabilities $capabilities,
        array $defaultInputModes,
        array $defaultOutputModes,
        array $skills,
        string $protocolVersion = '0.3.0',
        ?string $preferredTransport = 'JSONRPC'
    );
    
    // Getters
    public function getName(): string;
    public function getDescription(): string;
    public function getUrl(): string;
    public function getVersion(): string;
    public function getProtocolVersion(): string;
    public function getCapabilities(): AgentCapabilities;
    public function getDefaultInputModes(): array;
    public function getDefaultOutputModes(): array;
    public function getSkills(): array;
    
    // Optional properties
    public function setProvider(AgentProvider $provider): void;
    public function setSecuritySchemes(array $securitySchemes): void;
    public function setSecurity(array $security): void;
    public function setAdditionalInterfaces(array $additionalInterfaces): void;
    public function addAdditionalInterface(AgentInterface $interface): void;
    
    // Serialization
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### AgentCapabilities

Defines what an agent can do.

```php
class AgentCapabilities
{
    public function __construct(
        bool $canReceiveMessages = true,
        bool $canSendMessages = false,
        bool $canManageTasks = false,
        bool $supportsStreaming = false,
        bool $supportsPushNotifications = false
    );
    
    public function canReceiveMessages(): bool;
    public function canSendMessages(): bool;
    public function canManageTasks(): bool;
    public function supportsStreaming(): bool;
    public function supportsPushNotifications(): bool;
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### AgentSkill

Represents a specific capability or skill of an agent.

```php
class AgentSkill
{
    public function __construct(
        string $id,
        string $name,
        string $description,
        array $inputModes = [],
        array $outputModes = [],
        ?array $parameters = null
    );
    
    public function getId(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function getInputModes(): array;
    public function getOutputModes(): array;
    public function getParameters(): ?array;
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### Message

Represents a communication message between agents.

```php
class Message
{
    public function __construct(
        string $role,
        array $parts,
        ?string $id = null
    );
    
    public function getRole(): string;
    public function getParts(): array;
    public function getId(): ?string;
    public function getMetadata(): ?array;
    public function getExtensions(): ?array;
    public function getReferenceTaskIds(): ?array;
    public function getContextTaskIds(): ?array;
    
    public function setMetadata(?array $metadata): void;
    public function setExtensions(?array $extensions): void;
    public function setReferenceTaskIds(?array $taskIds): void;
    public function setContextTaskIds(?array $taskIds): void;
    public function addPart(PartInterface $part): void;
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### Task

Represents an asynchronous task.

```php
class Task
{
    public function __construct(
        string $id,
        Message $message,
        TaskStatus $status = TaskStatus::PENDING
    );
    
    public function getId(): string;
    public function getMessage(): Message;
    public function getStatus(): TaskStatus;
    public function getResult(): mixed;
    public function getError(): ?array;
    public function getProgress(): ?float;
    public function getCreatedAt(): DateTimeInterface;
    public function getUpdatedAt(): DateTimeInterface;
    
    public function setStatus(TaskStatus $status): void;
    public function setResult(mixed $result): void;
    public function setError(?array $error): void;
    public function setProgress(?float $progress): void;
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### TaskStatus

Enumeration of task statuses.

```php
enum TaskStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
```

## Message Parts

### TextPart

Plain text message content.

```php
class TextPart implements PartInterface
{
    public function __construct(string $text);
    
    public function getText(): string;
    public function getKind(): string; // Returns 'text'
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### FilePart

File attachment with metadata.

```php
class FilePart implements PartInterface
{
    public function __construct(
        string $name,
        string $mimeType,
        FileInterface $file
    );
    
    public function getName(): string;
    public function getMimeType(): string;
    public function getFile(): FileInterface;
    public function getKind(): string; // Returns 'file'
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### DataPart

Structured data content.

```php
class DataPart implements PartInterface
{
    public function __construct(mixed $data);
    
    public function getData(): mixed;
    public function getKind(): string; // Returns 'data'
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

## File Types

### FileWithBytes

File with inline binary data.

```php
class FileWithBytes implements FileInterface
{
    public function __construct(string $data);
    
    public function getData(): string;
    public function getType(): string; // Returns 'bytes'
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### FileWithUri

File referenced by URI.

```php
class FileWithUri implements FileInterface
{
    public function __construct(string $uri);
    
    public function getUri(): string;
    public function getType(): string; // Returns 'uri'
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

## Security

### Security Schemes

#### APIKeySecurityScheme

API key authentication.

```php
class APIKeySecurityScheme extends SecurityScheme
{
    public function __construct(
        string $name,
        string $in = 'header', // 'header', 'query', 'cookie'
        ?string $description = null
    );
    
    public function getName(): string;
    public function getIn(): string;
}
```

#### HTTPAuthSecurityScheme

HTTP authentication (Basic, Bearer, etc.).

```php
class HTTPAuthSecurityScheme extends SecurityScheme
{
    public function __construct(
        string $scheme, // 'basic', 'bearer', 'digest'
        ?string $bearerFormat = null,
        ?string $description = null
    );
    
    public function getScheme(): string;
    public function getBearerFormat(): ?string;
}
```

#### OAuth2SecurityScheme

OAuth 2.0 authentication.

```php
class OAuth2SecurityScheme extends SecurityScheme
{
    public function __construct(
        array $flows,
        ?string $description = null
    );
    
    public function getFlows(): array;
}
```

## Utilities

### HttpClient

HTTP client for making requests.

```php
class HttpClient
{
    public function __construct(?ClientInterface $client = null);
    
    public function post(string $url, array $data, array $headers = []): array;
    public function get(string $url, array $headers = []): array;
}
```

### JsonRpc

JSON-RPC 2.0 utilities.

```php
class JsonRpc
{
    public static function createRequest(
        string $method,
        mixed $params = null,
        mixed $id = null
    ): array;
    
    public static function createResponse(mixed $result, mixed $id): array;
    public static function createError(int $code, string $message, mixed $id = null, mixed $data = null): array;
    public static function parseRequest(string $json): array;
    public static function isValidRequest(array $request): bool;
}
```

## Exceptions

### A2AException

Base exception for A2A-related errors.

```php
class A2AException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?array $data = null
    );
    
    public function getData(): ?array;
    public function toArray(): array;
}
```

### InvalidRequestException

Exception for invalid requests.

```php
class InvalidRequestException extends A2AException
{
    public function __construct(
        string $message = 'Invalid request',
        ?array $data = null
    );
}
```

### TaskNotFoundException

Exception for missing tasks.

```php
class TaskNotFoundException extends A2AException
{
    public function __construct(string $taskId);
    
    public function getTaskId(): string;
}
```

## Error Codes

### A2AErrorCodes

Standard JSON-RPC error codes.

```php
class A2AErrorCodes
{
    public const PARSE_ERROR = -32700;
    public const INVALID_REQUEST = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INVALID_PARAMS = -32602;
    public const INTERNAL_ERROR = -32603;
}
```

### A2ASpecificErrors

A2A-specific error codes.

```php
class A2ASpecificErrors
{
    public const AGENT_NOT_AVAILABLE = -40001;
    public const TASK_NOT_FOUND = -40002;
    public const INVALID_MESSAGE_FORMAT = -40003;
    public const AUTHENTICATION_FAILED = -40004;
    public const RATE_LIMIT_EXCEEDED = -40005;
}
```

## Interfaces

### MessageHandlerInterface

Interface for message handlers.

```php
interface MessageHandlerInterface
{
    public function canHandle(Message $message): bool;
    public function handle(Message $message): Message;
}
```

### PartInterface

Interface for message parts.

```php
interface PartInterface
{
    public function getKind(): string;
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### FileInterface

Interface for file types.

```php
interface FileInterface
{
    public function getType(): string;
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### AgentExecutor

Interface for agent execution.

```php
interface AgentExecutor
{
    public function execute(Task $task): void;
    public function cancel(string $taskId): void;
}
```

## Factory Classes

### PartFactory

Factory for creating message parts.

```php
class PartFactory
{
    public static function create(array $data): PartInterface;
}
```

### FileFactory

Factory for creating file objects.

```php
class FileFactory
{
    public static function create(array $data): FileInterface;
}
```

## Usage Examples

### Basic Agent Setup

```php
use A2A\A2AServer;
use A2A\Models\{AgentCard, AgentCapabilities, AgentSkill};

$capabilities = new AgentCapabilities(
    canReceiveMessages: true,
    canSendMessages: true,
    canManageTasks: true
);

$skills = [
    new AgentSkill(
        id: 'text_processing',
        name: 'Text Processing',
        description: 'Process and analyze text content',
        inputModes: ['text/plain'],
        outputModes: ['text/plain', 'application/json']
    )
];

$agentCard = new AgentCard(
    name: 'Text Processor',
    description: 'An agent that processes text',
    url: 'https://my-agent.com',
    version: '1.0.0',
    capabilities: $capabilities,
    defaultInputModes: ['text/plain'],
    defaultOutputModes: ['text/plain'],
    skills: $skills
);

$server = new A2AServer($agentCard);
$server->start();
```

### Client Communication

```php
use A2A\A2AClient;
use A2A\Models\{Message, TextPart};

$client = new A2AClient('https://agent.example.com');

// Get agent info
$agentCard = $client->getAgentCard();

// Send message
$message = new Message('user', [
    new TextPart('Hello, agent!')
]);

$response = $client->sendMessage($message);
```

### Task Management

```php
use A2A\TaskManager;
use A2A\Models\{Message, TextPart, TaskStatus};

$taskManager = new TaskManager();

// Create task
$message = new Message('user', [
    new TextPart('Process this data')
]);

$task = $taskManager->createTask($message);

// Update task
$taskManager->updateTask(
    $task->getId(),
    TaskStatus::COMPLETED,
    ['result' => 'Processing complete']
);
```

This API reference provides comprehensive documentation for all public classes, methods, and interfaces in the A2A PHP implementation.