<?php
namespace App\Services;

interface SecurityServiceInterface {
    public function csrfToken(): string;
    public function csrfField(): string;
    public function verifyCsrf(): void;
    public function checkRateLimit(string $action, string $ip, int $limit, int $windowSeconds): void;
    public function recordRateLimit(string $action, string $ip): void;
    public function clearRateLimit(string $action, string $ip): void;
}
