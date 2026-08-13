<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLogService;
use App\Services\AuthServiceInterface;

class AuditLogMiddleware {
    public function __construct(
        private AuditLogService $auditLogService,
        private AuthServiceInterface $authService
    ) {}

    public function handle(Request $request): ?Response {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        if (file_exists($lockFile)) {
            return null;
        }

        $user = $this->authService->currentUser();
        if ($user && $user->isAdmin() && in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            $path = $request->getPath();
            if (str_starts_with($path, '/admin')) {
                $this->auditLogService->log(
                    'admin_action',
                    'request',
                    $path,
                    [
                        'method' => $request->getMethod(),
                        'params' => $this->filterSensitiveData($request->getPost())
                    ]
                );
            }
        }

        return null;
    }

    private function filterSensitiveData(array $data): array {
        $sensitiveFields = ['password', 'password_hash', 'verification_token', 'remember_token'];
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '********';
            }
        }
        return $data;
    }
}
