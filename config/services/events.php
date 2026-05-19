<?php

use App\Core\Events\EventDispatcherInterface;
use App\Core\Events\EventDispatcher;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Events\RefundProcessed;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;
use App\Events\UserRegistered;
use App\Events\EmailVerified;
use App\Events\StockUpdated;
use App\Events\ReviewSubmitted;
use App\Events\ReviewApproved;
use App\Events\SettingUpdated;
use App\Listeners\OrderListener;
use App\Listeners\AuthListener;
use App\Listeners\UserListener;
use App\Listeners\InventoryListener;
use App\Listeners\ReviewListener;
use App\Listeners\AuditListener;

use Psr\Log\LoggerInterface;

return function($c, array $config) {
    return [
        // Central Event Dispatcher
        EventDispatcherInterface::class => function($c) {
            $dispatcher = new EventDispatcher($c->get(LoggerInterface::class));
            
            // Order Listeners
            $dispatcher->addListener(
                OrderPlaced::class, 
                fn($e) => $c->get(OrderListener::class)->handle($e)
            );
            $dispatcher->addListener(
                OrderStatusUpdated::class, 
                fn($e) => $c->get(OrderListener::class)->handle($e)
            );
            $dispatcher->addListener(
                RefundProcessed::class, 
                fn($e) => $c->get(OrderListener::class)->handle($e)
            );

            // Auth Listeners
            $dispatcher->addListener(
                UserLoggedIn::class,
                fn($e) => $c->get(AuthListener::class)->handle($e)
            );
            $dispatcher->addListener(
                UserLoginFailed::class,
                fn($e) => $c->get(AuthListener::class)->handle($e)
            );

            // User Listeners
            $dispatcher->addListener(
                UserRegistered::class,
                fn($e) => $c->get(UserListener::class)->handle($e)
            );
            $dispatcher->addListener(
                EmailVerified::class,
                fn($e) => $c->get(UserListener::class)->handle($e)
            );

            // Inventory Listeners
            $dispatcher->addListener(
                StockUpdated::class,
                fn($e) => $c->get(InventoryListener::class)->handle($e)
            );

            // Review Listeners
            $dispatcher->addListener(
                ReviewSubmitted::class,
                fn($e) => $c->get(ReviewListener::class)->handle($e)
            );
            $dispatcher->addListener(
                ReviewApproved::class,
                fn($e) => $c->get(ReviewListener::class)->handle($e)
            );

            // Audit Listeners
            $dispatcher->addListener(
                SettingUpdated::class,
                fn($e) => $c->get(AuditListener::class)->handle($e)
            );
            
            return $dispatcher;
        },
    ];
};
