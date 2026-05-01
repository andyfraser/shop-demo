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
use App\Services\AttributeServiceInterface;
use App\Services\AttributeService;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ManualGateway;
use App\Services\ReturnServiceInterface;
use App\Services\ReturnService;
use App\Services\WishlistServiceInterface;
use App\Services\WishlistService;
use App\Services\ReviewServiceInterface;
use App\Services\ReviewService;
use App\Services\AddressServiceInterface;
use App\Services\AddressService;
use App\Services\ImageServiceInterface;
use App\Services\ImageService;

use App\Services\VatServiceInterface;
use App\Services\VatService;

use App\Commands\RotateLogsCommand;

return function(array $config) {
    return [
        // PSR-3 compliant file logger
        LoggerInterface::class => function() use ($config) {
            $isDebug = $config['app']['debug'] ?? false;
            $logPath = $config['app']['log_path'] ?? __DIR__ . '/../logs/app.log';
            return new FileLogger($logPath, $isDebug);
        },

        // Commands
        RotateLogsCommand::class => function($c) use ($config) {
            $logPath = $config['app']['log_path'] ?? __DIR__ . '/../logs/app.log';
            $logDir = dirname($logPath);
            $retention = $config['app']['log_retention_days'] ?? 30;
            return new RotateLogsCommand($logDir, $retention);
        },

        // PDO instance for database connectivity
        \PDO::class => function() {
            return Database::getConnection();
        },

        // Services
        SettingsServiceInterface::class => function($c) {
            return new SettingsService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        VatServiceInterface::class => function($c) {
            return new VatService();
        },
        AuthServiceInterface::class => function($c) {
            return new AuthService($c->get(\PDO::class), $c->get(SettingsServiceInterface::class), $c->get(LoggerInterface::class));
        },
        ProductServiceInterface::class => function($c) {
            return new ProductService($c->get(\PDO::class), $c->get(AttributeServiceInterface::class), $c->get(LoggerInterface::class));
        },
        CartServiceInterface::class => function($c) {
            return new CartService($c->get(\PDO::class), $c->get(ProductServiceInterface::class), $c->get(AuthServiceInterface::class), $c->get(VatServiceInterface::class), $c->get(LoggerInterface::class));
        },
        CategoryServiceInterface::class => function($c) {
            return new CategoryService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        OrderServiceInterface::class => function($c) {
            return new OrderService(
                $c->get(\PDO::class), 
                $c->get(LoggerInterface::class), 
                $c->get(VatServiceInterface::class),
                $c->get(PaymentServiceInterface::class),
                $c->get(EmailServiceInterface::class)
            );
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
        AttributeServiceInterface::class => function($c) {
            return new AttributeService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        PaymentServiceInterface::class => function($c) {
            $service = new PaymentService($c->get(LoggerInterface::class));
            $service->registerGateway(new ManualGateway());
            return $service;
        },
        ReturnServiceInterface::class => function($c) {
            return new ReturnService(
                $c->get(\PDO::class),
                $c->get(LoggerInterface::class),
                $c->get(OrderServiceInterface::class),
                $c->get(PaymentServiceInterface::class),
                $c->get(EmailServiceInterface::class)
            );
        },
        WishlistServiceInterface::class => function($c) {
            return new WishlistService(
                $c->get(\PDO::class),
                $c->get(ProductServiceInterface::class),
                $c->get(LoggerInterface::class)
            );
        },
        ReviewServiceInterface::class => function($c) {
            return new ReviewService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        AddressServiceInterface::class => function($c) {
            return new AddressService($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        ImageServiceInterface::class => function($c) {
            return new ImageService($c->get(LoggerInterface::class));
        },
    ];
};
