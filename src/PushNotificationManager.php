<?php

declare(strict_types=1);

namespace A2A;

use A2A\Models\PushNotificationConfig;
use A2A\Storage\Storage;

/**
 * Manager for push notification configurations
 */
class PushNotificationManager
{
    private array $configs = [];
    private Storage $storage;

    public function __construct(?Storage $storage = null)
    {
        $this->storage = $storage ?? new Storage();
    }

    /**
     * Set push notification config for a task
     */
    public function setConfig(string $taskId, PushNotificationConfig $config): bool
    {
        try {
            $this->configs[$taskId] = $config;
            $this->storage->savePushConfig($taskId, $config);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get push notification config for a task
     */
    public function getConfig(string $taskId): ?PushNotificationConfig
    {
        // Check in-memory cache first
        if (isset($this->configs[$taskId])) {
            return $this->configs[$taskId];
        }

        // Check persistent storage
        $config = $this->storage->getPushConfig($taskId);
        if ($config) {
            $this->configs[$taskId] = $config; // Cache it
        }
        return $config;
    }

    /**
     * List all push notification configs
     */
    public function listConfigs(): array
    {
        return $this->storage->listPushConfigs();
    }

    /**
     * Delete push notification config for a task
     */
    public function deleteConfig(string $taskId): bool
    {
        unset($this->configs[$taskId]); // Remove from cache
        return $this->storage->deletePushConfig($taskId);
    }

    /**
     * Check if config exists for a task
     */
    public function hasConfig(string $taskId): bool
    {
        return $this->getConfig($taskId) !== null;
    }
}
