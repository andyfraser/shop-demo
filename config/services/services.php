<?php

use Psr\Log\LoggerInterface;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\AttributeRepositoryInterface;
use App\Repositories\ReviewRepositoryInterface;
use App\Repositories\WishlistRepositoryInterface;
use App\Repositories\AddressRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRoleRepositoryInterface;
use App\Repositories\AuthRepositoryInterface;
use App\Repositories\SettingsRepositoryInterface;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\CartRepositoryInterface;
use App\Repositories\PromotionRepositoryInterface;
use App\Repositories\ReturnRepositoryInterface;
use App\Repositories\DeliveryRepositoryInterface;
use App\Repositories\SecurityRepositoryInterface;
use App\Repositories\ImageRepositoryInterface;
use App\Repositories\MigrationRepositoryInterface;

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

return function($c, array $config) {
    return [
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
            return new MigrationService($c->get(MigrationRepositoryInterface::class));
        },
        BackupServiceInterface::class => function($c) {
            return new BackupService($c->get(MigrationRepositoryInterface::class), $c->get(MigrationServiceInterface::class));
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
            return new DatabaseSeedService(
                $c->get(CategoryRepositoryInterface::class),
                $c->get(ProductRepositoryInterface::class),
                $c->get(DeliveryRepositoryInterface::class),
                $c->get(UserRepositoryInterface::class),
                $c->get(AttributeRepositoryInterface::class)
            );
        },
        UserRoleServiceInterface::class => function($c) {
            return new UserRoleService($c->get(UserRoleRepositoryInterface::class), $c->get(LoggerInterface::class));
        },
    ];
};
