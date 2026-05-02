<?php
namespace App\Middleware;

use App\Services\AuthServiceInterface;

class VerifiedMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle() {
        $user = $this->auth->currentUser();
        if ($user && !$user->isVerified()) {
            redirect('/cart?msg=verify_required');
        }
    }
}
