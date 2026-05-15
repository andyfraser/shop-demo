<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Services\SecurityServiceInterface;

class CsrfMiddleware {
    public function __construct(private SecurityServiceInterface $security) {}

    public function handle(Request $request): ?Response {
        if ($request->isPost()) {
            if (!$this->security->validateCsrf($request->getPost('csrf_token'))) {
                if ($request->isAjax()) {
                    return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF token.'], 403);
                }
                return new HtmlResponse('Invalid CSRF token. Please go back and try again.', 403);
            }
        }
        return null;
    }
}
