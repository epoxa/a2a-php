<?php

require_once __DIR__ . '/../vendor/autoload.php';

use A2A\A2AProtocol;
use A2A\Models\AgentCard;
use A2A\Models\Task;
use A2A\Models\Part;

echo "=== Task Management Example ===\n\n";

// Create an agent
$agentCard = new AgentCard(
    'task-manager-001',
    'Task Manager',
    'Demonstrates task management capabilities',
    '1.0.0',
    ['tasks', 'scheduling']
);

$protocol = new A2AProtocol($agentCard);

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

echo "Created {$task1->getId()}: {$task1->getDescription()}\n";
echo "Created {$task2->getId()}: {$task2->getDescription()}\n";
echo "Created {$task3->getId()}: {$task3->getDescription()}\n\n";

// Assign tasks
echo "Assigning tasks...\n";
$task1->assignTo('worker-agent-001');
$task2->assignTo('email-agent-001');
$task3->assignTo('preference-agent-001');

echo "Task 1 assigned to: {$task1->getAssignedTo()}\n";
echo "Task 2 assigned to: {$task2->getAssignedTo()}\n";
echo "Task 3 assigned to: {$task3->getAssignedTo()}\n\n";

// Update task statuses
echo "Updating task statuses...\n";
$task1->setStatus('in_progress');
$task2->setStatus('completed');
$task3->setStatus('in_progress');

echo "Task 1 status: {$task1->getStatus()}\n";
echo "Task 2 status: {$task2->getStatus()} - Completed at: {$task2->getCompletedAt()?->format('Y-m-d H:i:s')}\n";
echo "Task 3 status: {$task3->getStatus()}\n\n";

// Add parts to tasks
echo "Adding parts to task 1...\n";
$part1 = new Part('validation', 'Email validation completed');
$part2 = new Part('database', 'User record created');
$task1->addPart($part1);
$task1->addPart($part2);

echo "Task 1 now has " . count($task1->getParts()) . " parts\n\n";

// Convert to array for storage/transmission
echo "Task 1 as array:\n";
echo json_encode($task1->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Complete remaining tasks
echo "Completing remaining tasks...\n";
$task1->setStatus('completed');
$task3->setStatus('completed');

$completedTasks = array_filter(
    [$task1, $task2, $task3],
    fn(Task $task) => $task->isCompleted()
);

echo "Completed " . count($completedTasks) . " out of 3 tasks\n";

echo "\nTask management example completed!\n";