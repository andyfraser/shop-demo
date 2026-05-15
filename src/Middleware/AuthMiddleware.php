<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Services\AuthServiceInterface;

class AuthMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle(Request $request): ?Response {
        if (!$this->auth->currentUser()) {
            $_SESSION['redirect_after_login'] = $request->getUri();
            return new RedirectResponse('/login');
        }
        return null;
    }
}
