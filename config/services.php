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
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\CategoryRepository;
use App\Repositories\AttributeRepositoryInterface;
use App\Repositories\AttributeRepository;
use App\Repositories\ReviewRepositoryInterface;
use App\Repositories\ReviewRepository;
use App\Repositories\WishlistRepositoryInterface;
use App\Repositories\WishlistRepository;
use App\Repositories\AddressRepositoryInterface;
use App\Repositories\AddressRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRoleRepositoryInterface;
use App\Repositories\UserRoleRepository;
use App\Repositories\AuthRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\SettingsRepositoryInterface;
use App\Repositories\SettingsRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\CartRepositoryInterface;
use App\Repositories\CartRepository;
use App\Repositories\PromotionRepositoryInterface;
use App\Repositories\PromotionRepository;
use App\Repositories\ReturnRepositoryInterface;
use App\Repositories\ReturnRepository;
use App\Repositories\DeliveryRepositoryInterface;
use App\Repositories\DeliveryRepository;
use App\Repositories\SecurityRepositoryInterface;
use App\Repositories\SecurityRepository;
use App\Repositories\ImageRepositoryInterface;
use App\Repositories\ImageRepository;
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
use App\Services\MigrationServiceInterface;
use App\Services\MigrationService;
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
use App\Services\ImageCleanupServiceInterface;
use App\Services\ImageCleanupService;
use App\Services\ImageServiceInterface;
use App\Services\ImageService;
use App\Services\DatabaseSeedServiceInterface;
use App\Services\DatabaseSeedService;
use App\Services\UserRoleServiceInterface;
use App\Services\UserRoleService;

use App\Services\VatServiceInterface;
use App\Services\VatService;
use App\Services\PromotionServiceInterface;
use App\Services\PromotionService;

use App\Commands\RotateLogsCommand;
use App\Commands\ImageCleanupCommand;
use App\Commands\SeedCommand;

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
        ImageCleanupCommand::class => function($c) {
            return new ImageCleanupCommand($c->get(ImageCleanupServiceInterface::class));
        },
        App\Commands\MigrateCommand::class => function($c) {
            return new App\Commands\MigrateCommand($c->get(MigrationServiceInterface::class));
        },
        App\Commands\MigrateRollbackCommand::class => function($c) {
            return new App\Commands\MigrateRollbackCommand($c->get(MigrationServiceInterface::class));
        },
        SeedCommand::class => function($c) {
            return new SeedCommand($c->get(DatabaseSeedServiceInterface::class));
        },

        // PDO instance for database connectivity
        \PDO::class => function() {
            return Database::getConnection();
        },

        // Repositories
        ProductRepositoryInterface::class => function($c) {
            return new ProductRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        CategoryRepositoryInterface::class => function($c) {
            return new CategoryRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        AttributeRepositoryInterface::class => function($c) {
            return new AttributeRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        ReviewRepositoryInterface::class => function($c) {
            return new ReviewRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        WishlistRepositoryInterface::class => function($c) {
            return new WishlistRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        AddressRepositoryInterface::class => function($c) {
            return new AddressRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        UserRepositoryInterface::class => function($c) {
            return new UserRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        UserRoleRepositoryInterface::class => function($c) {
            return new UserRoleRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        AuthRepositoryInterface::class => function($c) {
            return new AuthRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        SettingsRepositoryInterface::class => function($c) {
            return new SettingsRepository($c->get(\PDO::class));
        },
        OrderRepositoryInterface::class => function($c) {
            return new OrderRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        CartRepositoryInterface::class => function($c) {
            return new CartRepository($c->get(\PDO::class));
        },
        PromotionRepositoryInterface::class => function($c) {
            return new PromotionRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        ReturnRepositoryInterface::class => function($c) {
            return new ReturnRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        DeliveryRepositoryInterface::class => function($c) {
            return new DeliveryRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        SecurityRepositoryInterface::class => function($c) {
            return new SecurityRepository($c->get(\PDO::class));
        },
        ImageRepositoryInterface::class => function($c) {
            return new ImageRepository($c->get(\PDO::class));
        },

        // Services
        SettingsServiceInterface::class => function($c) {
            return new SettingsService($c->get(SettingsRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        VatServiceInterface::class => function($c) {
            return new VatService();
        },
        AuthServiceInterface::class => function($c) {
            return new AuthService($c->get(AuthRepositoryInterface::class), $c->get(SettingsServiceInterface::class), $c->get(LoggerInterface::class));
        },
        ProductServiceInterface::class => function($c) {
            return new ProductService(
                $c->get(ProductRepositoryInterface::class), 
                $c->get(AttributeServiceInterface::class), 
                $c->get(PromotionServiceInterface::class),
                $c->get(LoggerInterface::class)
            );
        },
        CartServiceInterface::class => function($c) {
            return new CartService(
                $c->get(CartRepositoryInterface::class),
                $c->get(ProductServiceInterface::class),
                $c->get(AuthServiceInterface::class),
                $c->get(VatServiceInterface::class),
                $c->get(PromotionServiceInterface::class),
                $c->get(OrderServiceInterface::class),
                $c->get(LoggerInterface::class)
            );
        },
        CategoryServiceInterface::class => function($c) {
            return new CategoryService($c->get(CategoryRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        OrderServiceInterface::class => function($c) {
            return new OrderService(
                $c->get(OrderRepositoryInterface::class), 
                $c->get(LoggerInterface::class), 
                $c->get(VatServiceInterface::class),
                $c->get(PaymentServiceInterface::class),
                $c->get(EmailServiceInterface::class)
            );
        },

        UserServiceInterface::class => function($c) {
            return new UserService($c->get(UserRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        EmailServiceInterface::class => function($c) {
            return new EmailService($c->get(SettingsServiceInterface::class), $c->get(LoggerInterface::class));
        },
        SecurityServiceInterface::class => function($c) {
            return new SecurityService($c->get(SecurityRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        MigrationServiceInterface::class => function($c) {
            return new MigrationService($c->get(\PDO::class));
        },
        BackupServiceInterface::class => function($c) {
            return new BackupService($c->get(\PDO::class), $c->get(MigrationServiceInterface::class));
        },
        DeliveryServiceInterface::class => function($c) {
            return new DeliveryService($c->get(DeliveryRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        AttributeServiceInterface::class => function($c) {
            return new AttributeService($c->get(AttributeRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        PaymentServiceInterface::class => function($c) {
            $service = new PaymentService($c->get(LoggerInterface::class));
            $service->registerGateway(new ManualGateway());
            return $service;
        },
        ReturnServiceInterface::class => function($c) {
            return new ReturnService(
                $c->get(ReturnRepositoryInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(OrderServiceInterface::class),
                $c->get(PaymentServiceInterface::class),
                $c->get(EmailServiceInterface::class)
            );
        },
        WishlistServiceInterface::class => function($c) {
            return new WishlistService(
                $c->get(WishlistRepositoryInterface::class),
                $c->get(ProductServiceInterface::class),
                $c->get(LoggerInterface::class)
            );
        },
        ReviewServiceInterface::class => function($c) {
            return new ReviewService($c->get(ReviewRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        AddressServiceInterface::class => function($c) {
            return new AddressService($c->get(AddressRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        ImageServiceInterface::class => function($c) {
            return new ImageService($c->get(LoggerInterface::class));
        },
        ImageCleanupServiceInterface::class => function($c) {
            return new ImageCleanupService($c->get(ImageRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
        PromotionServiceInterface::class => function($c) {
            return new PromotionService(
                $c->get(PromotionRepositoryInterface::class), 
                $c->get(LoggerInterface::class),
                $c->get(CategoryServiceInterface::class),
                $c->get(OrderServiceInterface::class)
            );
        },
        DatabaseSeedServiceInterface::class => function($c) {
            return new DatabaseSeedService($c->get(\PDO::class));
        },
        UserRoleServiceInterface::class => function($c) {
            return new UserRoleService($c->get(UserRoleRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
    ];
};
