<?php

/**
 * Dependency Injection Service Registrations
 * 
 * This file returns an array mapping service identifiers (interfaces or classes)
 * to factory closures. These are loaded and registered in the DI Container 
 * within index.php.
 */

use Psr\Log\LoggerInterface;
use App\Core\FileLogger;
use App\Core\Database;

return function(array $config) {
    return [
        // PSR-3 compliant file logger
        LoggerInterface::class => function() use ($config) {
            $isDebug = $config['app']['debug'] ?? false;
            $retention = $config['app']['log_retention_days'] ?? 30;
            return new FileLogger(__DIR__ . '/../logs/app.log', $isDebug, $retention);
        },

        // PDO instance for database connectivity
        \PDO::class => function() {
            return Database::getConnection();
        },
    ];
};
