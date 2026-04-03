<?php
namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware {
    public function handle() {
        if (!AuthService::currentUser()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
        }
    }
}
