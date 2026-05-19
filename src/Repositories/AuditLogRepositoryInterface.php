<?php

namespace App\Repositories;

use App\Models\AuditLog;

interface AuditLogRepositoryInterface {
    public function log(array $data): void;
    public function find(array $criteria = [], int $limit = 50, int $offset = 0): array;
    public function count(array $criteria = []): int;
}
