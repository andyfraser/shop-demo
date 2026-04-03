<?php
namespace App\Middleware;

use App\Services\AuthService;

class AdminMiddleware {
    public function handle() {
        if (!AuthService::currentUser()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
        }
        if (!AuthService::isAdmin()) {
            redirect('/');
        }
    }
}
