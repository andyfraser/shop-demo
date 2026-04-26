<?php
namespace App\Middleware;

use App\Services\AuthServiceInterface;

class AuthMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle() {
        if (!$this->auth->currentUser()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
        }
    }
}
