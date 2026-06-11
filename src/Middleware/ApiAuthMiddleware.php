<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;
use App\Services\AuthServiceInterface;

class ApiAuthMiddleware {
    public function __construct(private AuthServiceInterface $auth) {}

    public function handle(Request $request): ?Response {
        if (!$this->auth->currentUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication is required to access this resource.'
                ]
            ], 401);
        }
        return null;
    }
}
