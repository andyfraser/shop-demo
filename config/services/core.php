<?php

use Psr\Log\LoggerInterface;
use App\Core\FileLogger;
use App\Core\Database;
use App\Core\Events\EventDispatcherInterface;
use App\Core\Events\EventDispatcher;
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
            $cachePath = $config['app']['cache_path'] ?? __DIR__ . '/../../storage/cache';
            return new FileCache($cachePath);
        },

        // Central Event Dispatcher
        EventDispatcherInterface::class => function($c) {
            $dispatcher = new EventDispatcher();
            
            // Register Listeners
            $dispatcher->addListener(
                \App\Events\OrderPlaced::class, 
                new \App\Listeners\OrderEmailListener($c->get(\App\Services\EmailServiceInterface::class))
            );

            $dispatcher->addListener(
                \App\Events\UserLoggedIn::class,
                new \App\Listeners\AuthListener($c->get(LoggerInterface::class))
            );

            $dispatcher->addListener(
                \App\Events\UserLoginFailed::class,
                new \App\Listeners\AuthListener($c->get(LoggerInterface::class))
            );
            
            return $dispatcher;
        },
    ];
};
