<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Services\AuthServiceInterface;

class VerifiedMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle(Request $request): ?Response {
        $user = $this->auth->currentUser();
        if ($user && !$user->isVerified()) {
            return new RedirectResponse('/cart?msg=verify_required');
        }
        return null;
    }
}
