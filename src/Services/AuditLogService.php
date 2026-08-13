<?php

namespace App\Services;

use App\Repositories\AuditLogRepositoryInterface;
use App\Core\Request;

class AuditLogService {
    public function __construct(
        private AuditLogRepositoryInterface $repository,
        private AuthServiceInterface $authService
    ) {}

    public function log(string $action, ?string $resourceType = null, ?string $resourceId = null, ?array $details = null): void {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        if (file_exists($lockFile)) {
            return;
        }

        $user = $this->authService->currentUser();
        
        $this->repository->log([
            'user_id'       => $user?->id,
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'details'       => $details ? json_encode($details) : null,
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public function find(array $criteria = [], int $limit = 50, int $offset = 0): array {
        return $this->repository->find($criteria, $limit, $offset);
    }

    public function count(array $criteria = []): int {
        return $this->repository->count($criteria);
    }
}
