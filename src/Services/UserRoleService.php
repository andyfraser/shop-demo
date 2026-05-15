<?php

namespace App\Services;

use App\Models\UserRole;
use App\Repositories\UserRoleRepositoryInterface;
use Psr\Log\LoggerInterface;

class UserRoleService implements UserRoleServiceInterface {
    public function __construct(
        private UserRoleRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        return $this->repository->getAll();
    }

    public function findById(int $id): ?UserRole {
        return $this->repository->findById($id);
    }

    public function findBySlug(string $slug): ?UserRole {
        return $this->repository->findBySlug($slug);
    }

    public function save(array|UserRole $data, int $id = 0): int {
        return $this->repository->save($data, $id);
    }

    public function delete(int $id): void {
        $this->repository->delete($id);
    }
}
