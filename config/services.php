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
use App\Services\AuthServiceInterface;
use App\Services\AuthService;
use App\Services\CartServiceInterface;
use App\Services\CartService;
use App\Services\ProductServiceInterface;
use App\Services\ProductService;
use App\Services\CategoryServiceInterface;
use App\Services\CategoryService;
use App\Services\OrderServiceInterface;
use App\Services\OrderService;
use App\Services\UserServiceInterface;
use App\Services\UserService;
use App\Services\SettingsServiceInterface;
use App\Services\SettingsService;
use App\Services\EmailServiceInterface;
use App\Services\EmailService;
use App\Services\SecurityServiceInterface;
use App\Services\SecurityService;
use App\Services\BackupServiceInterface;
use App\Services\BackupService;
use App\Services\DeliveryServiceInterface;
use App\Services\DeliveryService;

return function(array $config) {
    return [
        // PSR-3 compliant file logger
        LoggerInterface::class => function() use ($config) {
            $isDebug = $config['app']['debug'] ?? false;
            $logPath = $config['app']['log_path'] ?? __DIR__ . '/../logs/app.log';
            $retention = $config['app']['log_retention_days'] ?? 30;
            return new FileLogger($logPath, $isDebug, $retention);
        },

        // PDO instance for database connectivity
        \PDO::class => function() {
            return Database::getConnection();
        },

        // Services
        SettingsServiceInterface::class => function($c) {
            return new SettingsService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        AuthServiceInterface::class => function($c) {
            return new AuthService($c->get(\PDO::class), $c->get(SettingsServiceInterface::class), $c->get(LoggerInterface::class));
        },
        ProductServiceInterface::class => function($c) {
            return new ProductService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        CartServiceInterface::class => function($c) {
            return new CartService($c->get(ProductServiceInterface::class), $c->get(AuthServiceInterface::class));
        },
        CategoryServiceInterface::class => function($c) {
            return new CategoryService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        OrderServiceInterface::class => function($c) {
            return new OrderService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        UserServiceInterface::class => function($c) {
            return new UserService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        EmailServiceInterface::class => function($c) {
            return new EmailService($c->get(SettingsServiceInterface::class), $c->get(LoggerInterface::class));
        },
        SecurityServiceInterface::class => function($c) {
            return new SecurityService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        BackupServiceInterface::class => function($c) {
            return new BackupService($c->get(\PDO::class));
        },
        DeliveryServiceInterface::class => function($c) {
            return new DeliveryService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
    ];
};
