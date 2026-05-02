<?php
namespace App\Middleware;

use App\Services\AuthServiceInterface;

class GuestMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle() {
        if ($this->auth->currentUser()) {
            redirect('/');
        }
    }
}
