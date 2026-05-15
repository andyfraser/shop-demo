<?php
namespace App\Repositories;

interface SecurityRepositoryInterface {
    public function countRateLimits(string $action, string $ip, string $since): int;
    public function recordRateLimit(string $action, string $ip): void;
    public function clearRateLimit(string $action, string $ip): void;
}
