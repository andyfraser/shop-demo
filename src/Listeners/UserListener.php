<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\UserRegistered;
use App\Events\EmailVerified;
use App\Services\EmailServiceInterface;
use Psr\Log\LoggerInterface;

class UserListener implements ListenerInterface {
    public function __construct(
        private EmailServiceInterface $emailService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof UserRegistered) {
            $this->handleUserRegistered($event);
        } elseif ($event instanceof EmailVerified) {
            $this->handleEmailVerified($event);
        }
    }

    private function handleUserRegistered(UserRegistered $event): void {
        $user = $event->user;
        if ($user->verification_token) {
            $this->emailService->sendVerificationEmail(
                $user->email,
                $user->name,
                $user->verification_token
            );
        }
    }

    private function handleEmailVerified(EmailVerified $event): void {
        $this->logger->info("User email verified: {$event->user->email}", [
            'user_id' => $event->user->id
        ]);
    }
}
