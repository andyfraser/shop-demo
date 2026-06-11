<?php

use Psr\Log\LoggerInterface;
use App\Core\FileLogger;
use App\Core\Database;
use App\Core\Cache\CacheInterface;
use App\Core\Cache\FileCache;

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

        // File-based cache
        CacheInterface::class => function() use ($config) {
            $cachePath = (defined('TESTING') && TESTING)
                ? __DIR__ . '/../../storage/cache_test'
                : ($config['app']['cache_path'] ?? __DIR__ . '/../../storage/cache');
            return new FileCache($cachePath);
        },

    ];
};
