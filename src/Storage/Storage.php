<?php

declare(strict_types=1);

namespace A2A\Storage;

use A2A\Models\Task;
use A2A\Models\PushNotificationConfig;

/**
 * Simple file-based storage for task and push notification persistence
 */
class Storage
{
    private string $tasksFile;
    private string $pushConfigsFile;
    private array $tasks = [];
    private array $pushConfigs = [];

    public function __construct(string $dataDir = '/tmp/a2a_storage')
    {
        // Create data directory if it doesn't exist
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $this->tasksFile = $dataDir . '/tasks.json';
        $this->pushConfigsFile = $dataDir . '/push_configs.json';

        $this->loadTasks();
        $this->loadPushConfigs();
    }

    /**
     * Save a task
     */
    public function saveTask(Task $task): void
    {
        $this->tasks[$task->getId()] = $task->toArray();
        $this->saveTasks();
    }

    /**
     * Get a task by ID
     */
    public function getTask(string $taskId): ?Task
    {
        if (!isset($this->tasks[$taskId])) {
            return null;
        }

        return Task::fromArray($this->tasks[$taskId]);
    }

    /**
     * Check if task exists
     */
    public function taskExists(string $taskId): bool
    {
        return isset($this->tasks[$taskId]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(string $taskId, string $status): bool
    {
        if (!isset($this->tasks[$taskId])) {
            return false;
        }

        $this->tasks[$taskId]['status']['state'] = $status;
        $this->tasks[$taskId]['status']['timestamp'] = date('c');
        $this->saveTasks();
        return true;
    }

    /**
     * Save push notification config
     */
    public function savePushConfig(string $taskId, PushNotificationConfig $config): void
    {
        $this->pushConfigs[$taskId] = $config->toArray();
        $this->savePushConfigs();
    }

    /**
     * Get push notification config
     */
    public function getPushConfig(string $taskId): ?PushNotificationConfig
    {
        if (!isset($this->pushConfigs[$taskId])) {
            return null;
        }

        return PushNotificationConfig::fromArray($this->pushConfigs[$taskId]);
    }

    /**
     * List all push configs
     */
    public function listPushConfigs(): array
    {
        $configs = [];
        foreach ($this->pushConfigs as $taskId => $configData) {
            $configs[] = PushNotificationConfig::fromArray($configData);
        }
        return $configs;
    }

    /**
     * Delete push notification config
     */
    public function deletePushConfig(string $taskId): bool
    {
        if (!isset($this->pushConfigs[$taskId])) {
            return false;
        }

        unset($this->pushConfigs[$taskId]);
        $this->savePushConfigs();
        return true;
    }

    /**
     * Load tasks from file
     */
    private function loadTasks(): void
    {
        if (file_exists($this->tasksFile)) {
            $data = file_get_contents($this->tasksFile);
            $this->tasks = json_decode($data, true) ?: [];
        }
    }

    /**
     * Save tasks to file
     */
    private function saveTasks(): void
    {
        file_put_contents($this->tasksFile, json_encode($this->tasks, JSON_PRETTY_PRINT));
    }

    /**
     * Load push configs from file
     */
    private function loadPushConfigs(): void
    {
        if (file_exists($this->pushConfigsFile)) {
            $data = file_get_contents($this->pushConfigsFile);
            $this->pushConfigs = json_decode($data, true) ?: [];
        }
    }

    /**
     * Save push configs to file
     */
    private function savePushConfigs(): void
    {
        file_put_contents($this->pushConfigsFile, json_encode($this->pushConfigs, JSON_PRETTY_PRINT));
    }
}
