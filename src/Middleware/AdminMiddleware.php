<?php
namespace App\Middleware;

use App\Services\AuthServiceInterface;

class AdminMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

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
