<?php

use App\Core\Events\EventDispatcherInterface;
use App\Core\Events\EventDispatcher;
use App\Events\OrderPlaced;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;
use App\Listeners\OrderEmailListener;
use App\Listeners\AuthListener;

return function($c, array $config) {
    return [
        // Central Event Dispatcher
        EventDispatcherInterface::class => function($c) {
            $dispatcher = new EventDispatcher();
            
            // Register Listeners
            $dispatcher->addListener(
                OrderPlaced::class, 
                $c->get(OrderEmailListener::class)
            );

            $dispatcher->addListener(
                UserLoggedIn::class,
                $c->get(AuthListener::class)
            );

            $dispatcher->addListener(
                UserLoginFailed::class,
                $c->get(AuthListener::class)
            );
            
            return $dispatcher;
        },
    ];
};
