<?php
namespace App\Services;

use App\Repositories\SecurityRepositoryInterface;
use Psr\Log\LoggerInterface;

class SecurityService implements SecurityServiceInterface {
    public function __construct(
        private SecurityRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function csrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . h($this->csrfToken()) . '">';
    }

    public function validateCsrf(?string $passed): bool {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        $stored = $_SESSION['csrf_token'] ?? '';
        return !empty($passed) && hash_equals($stored, $passed);
    }

    public function isRateLimited(string $action, string $ip, int $limit, int $windowSeconds): bool {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $count = $this->repository->countRateLimits($action, $ip, $since);
        
        if ($count >= $limit) {
            $this->logger->warning("Rate limit hit for action '{action}' from IP {ip}", [
                'action' => $action,
                'ip' => $ip
            ]);
            return true;
        }
        return false;
    }

    public function recordRateLimit(string $action, string $ip): void {
        $this->repository->recordRateLimit($action, $ip);
    }

    public function clearRateLimit(string $action, string $ip): void {
        $this->repository->clearRateLimit($action, $ip);
    }
}
