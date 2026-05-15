<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Services\AuthServiceInterface;
use Psr\Log\LoggerInterface;

class AdminMiddleware {
    public function __construct(
        private AuthServiceInterface $auth,
        private ?LoggerInterface $logger = null
    ) {}

    public function handle(Request $request): ?Response {
        if (!$this->auth->currentUser()) {
            $_SESSION['redirect_after_login'] = $request->getUri();
            return new RedirectResponse('/login');
        }
        if (!$this->auth->isAdmin()) {
            if ($this->logger) {
                $user = $this->auth->currentUser();
                $this->logger->warning("Unauthorized admin access attempt by user {email} (ID: {id}) for {uri}", [
                    'email' => $user->email,
                    'id' => $user->id,
                    'uri' => $request->getUri()
                ]);
            }
            return new RedirectResponse('/');
        }
        return null;
    }
}
