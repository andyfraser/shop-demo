<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;
use Psr\Log\LoggerInterface;

class AuthListener implements ListenerInterface {
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof UserLoggedIn) {
            $this->logger->info("Auth Event: User logged in: {email}", ['email' => $event->user->email]);
        } elseif ($event instanceof UserLoginFailed) {
            $this->logger->warning("Auth Event: Failed login attempt for {email} from IP {ip}", [
                'email' => $event->email,
                'ip' => $event->ip
            ]);
        }
    }
}
