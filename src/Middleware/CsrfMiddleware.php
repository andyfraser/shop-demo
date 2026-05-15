<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Services\SecurityServiceInterface;
use Psr\Log\LoggerInterface;

class CsrfMiddleware {
    public function __construct(
        private SecurityServiceInterface $security,
        private ?LoggerInterface $logger = null
    ) {}

    public function handle(Request $request): ?Response {
        if ($request->isPost()) {
            if (!$this->security->validateCsrf($request->getPost('csrf_token'))) {
                if ($this->logger) {
                    $this->logger->warning("CSRF validation failed for {method} {uri} from IP {ip}", [
                        'method' => $request->getMethod(),
                        'uri' => $request->getUri(),
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }

                if ($request->isAjax()) {
                    return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF token.'], 403);
                }
                return new HtmlResponse('Invalid CSRF token. Please go back and try again.', 403);
            }
        }
        return null;
    }
}
