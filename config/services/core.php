<?php

use Psr\Log\LoggerInterface;
use App\Core\FileLogger;
use App\Core\Database;

return function($c, array $config) {
    return [
        // PSR-3 compliant file logger
        LoggerInterface::class => function() use ($config) {
            $isDebug = $config['app']['debug'] ?? false;
            $logPath = $config['app']['log_path'] ?? __DIR__ . '/../../logs/app.log';
            return new FileLogger($logPath, $isDebug);
        },

        // PDO instance for database connectivity
        \PDO::class => function() {
            return Database::getConnection();
        },
    ];
};
