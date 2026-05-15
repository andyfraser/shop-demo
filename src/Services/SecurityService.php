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

    public function verifyCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) @session_start();
            $passed = $_POST['csrf_token'] ?? '';
            $stored = $_SESSION['csrf_token'] ?? '';
            if (empty($passed) || !hash_equals($stored, $passed)) {
                $this->logger->error("CSRF token verification failed for {method} {uri}", [
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'uri' => $_SERVER['REQUEST_URI']
                ]);
                
                http_response_code(403);
                if (is_ajax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
                } else {
                    echo 'Invalid CSRF token. Please go back and try again.';
                }
                exit;
            }
        }
    }

    public function checkRateLimit(string $action, string $ip, int $limit, int $windowSeconds): void {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $count = $this->repository->countRateLimits($action, $ip, $since);
        
        if ($count >= $limit) {
            $this->logger->warning("Rate limit hit for action '{action}' from IP {ip}", [
                'action' => $action,
                'ip' => $ip
            ]);
            http_response_code(429);
            die('Too many attempts. Please try again later.');
        }
    }

    public function recordRateLimit(string $action, string $ip): void {
        $this->repository->recordRateLimit($action, $ip);
    }

    public function clearRateLimit(string $action, string $ip): void {
        $this->repository->clearRateLimit($action, $ip);
    }
}
