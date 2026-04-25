<?php
namespace App\Middleware;

use App\Services\AuthService;

class AdminMiddleware {
    private AuthService $auth;

    public function __construct(AuthService $auth) {
        $this->auth = $auth;
    }

    public function handle() {
        if (!$this->auth->currentUser()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
        }
        if (!$this->auth->isAdmin()) {
            redirect('/');
        }
    }
}
