<?php

use Psr\Log\LoggerInterface;
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
use App\Repositories\MigrationRepositoryInterface;
use App\Repositories\MigrationRepository;
use App\Repositories\CurrencyRepositoryInterface;
use App\Repositories\CurrencyRepository;
use App\Repositories\JobRepositoryInterface;
use App\Repositories\JobRepository;
use App\Repositories\VirtualProductRepositoryInterface;
use App\Repositories\VirtualProductRepository;

return function($c, array $config) {
    return [
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
        MigrationRepositoryInterface::class => function($c) {
            return new MigrationRepository($c->get(\PDO::class));
        },
        \App\Repositories\AuditLogRepositoryInterface::class => function($c) {
            return new \App\Repositories\AuditLogRepository($c->get(\PDO::class));
        },
        CurrencyRepositoryInterface::class => function($c) {
            return new CurrencyRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
        JobRepositoryInterface::class => function($c) {
            return new JobRepository($c->get(\PDO::class));
        },
        VirtualProductRepositoryInterface::class => function($c) {
            return new VirtualProductRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        },
    ];
};
