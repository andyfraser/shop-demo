<?php
namespace App\Services;

interface SecurityServiceInterface {
    public function csrfToken(): string;
    public function csrfField(): string;
    public function validateCsrf(?string $token): bool;
    public function isRateLimited(string $action, string $ip, int $limit, int $windowSeconds): bool;
    public function recordRateLimit(string $action, string $ip): void;
    public function clearRateLimit(string $action, string $ip): void;
}
