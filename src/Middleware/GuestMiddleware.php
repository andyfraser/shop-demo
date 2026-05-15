<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Services\AuthServiceInterface;

class GuestMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle(Request $request): ?Response {
        if ($this->auth->currentUser()) {
            return new RedirectResponse('/');
        }
        return null;
    }
}
