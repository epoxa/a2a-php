<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AProtocol_v030;
use A2A\Models\v030\AgentCard;
use A2A\Models\AgentCapabilities;
use A2A\Models\AgentSkill;
use A2A\Models\v030\Task;
use A2A\Models\TaskState;
use A2A\Models\TaskStatus;

echo "=== Task Management Example ===\n\n";

// Create an agent
$capabilities = new AgentCapabilities();
$skill = new AgentSkill('tasks', 'Task Management', 'Task management capabilities', ['tasks', 'scheduling']);

$agentCard = new AgentCard(
    'Task Manager',
    'Demonstrates task management capabilities',
    'https://example.com/task-manager',
    '1.0.0',
    $capabilities,
    ['text'],
    ['text'],
    [$skill]
);

$protocol = new A2AProtocol_v030($agentCard);

// Create multiple tasks
echo "Creating tasks...\n";
$task1 = $protocol->createTask(
    'Process user registration',
    ['user_id' => 123, 'priority' => 'high']
);

$task2 = $protocol->createTask(
    'Send welcome email',
    ['user_id' => 123, 'template' => 'welcome']
);

$task3 = $protocol->createTask(
    'Update user preferences',
    ['user_id' => 123, 'preferences' => ['newsletter' => true]]
);

echo "Created {$task1->getId()}: {$task1->getMetadata()['description']}\n";
echo "Created {$task2->getId()}: {$task2->getMetadata()['description']}\n";
echo "Created {$task3->getId()}: {$task3->getMetadata()['description']}\n\n";

// Update task metadata
echo "Updating task metadata...\n";
$metadata1 = $task1->getMetadata();
$metadata1['assigned_to'] = 'worker-agent-001';
$task1->setMetadata($metadata1);

$metadata2 = $task2->getMetadata();
$metadata2['assigned_to'] = 'email-agent-001';
$task2->setMetadata($metadata2);

$metadata3 = $task3->getMetadata();
$metadata3['assigned_to'] = 'preference-agent-001';
$task3->setMetadata($metadata3);

echo "Task 1 assigned to: {$task1->getMetadata()['assigned_to']}\n";
echo "Task 2 assigned to: {$task2->getMetadata()['assigned_to']}\n";
echo "Task 3 assigned to: {$task3->getMetadata()['assigned_to']}\n\n";

// Update task statuses
echo "Updating task statuses...\n";
$task1->setStatus(new TaskStatus(TaskState::WORKING));
$task2->setStatus(new TaskStatus(TaskState::COMPLETED));
$task3->setStatus(new TaskStatus(TaskState::WORKING));

echo "Task 1 status: {$task1->getStatus()->getState()->value}\n";
echo "Task 2 status: {$task2->getStatus()->getState()->value}\n";
echo "Task 3 status: {$task3->getStatus()->getState()->value}\n\n";

// Add metadata to tasks
echo "Adding metadata to task 1...\n";
$metadata = $task1->getMetadata();
$metadata['validation'] = 'Email validation completed';
$metadata['database'] = 'User record created';
$task1->setMetadata($metadata);

echo "Task 1 now has " . count($task1->getMetadata()) . " metadata fields\n\n";

// Convert to array for storage/transmission
echo "Task 1 as array:\n";
echo json_encode($task1->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Complete remaining tasks
echo "Completing remaining tasks...\n";
$task1->setStatus(new TaskStatus(TaskState::COMPLETED));
$task3->setStatus(new TaskStatus(TaskState::COMPLETED));

$completedTasks = array_filter(
    [$task1, $task2, $task3],
    fn(Task $task) => $task->getStatus()->getState() === TaskState::COMPLETED
);

echo "Completed " . count($completedTasks) . " out of 3 tasks\n";

echo "\nTask management example completed!\n";