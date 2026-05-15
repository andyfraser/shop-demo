<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

class UserService implements UserServiceInterface {
    public function __construct(
        private UserRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        return $this->repository->getAll();
    }

    public function findById(int $id): ?User {
        return $this->repository->findById($id);
    }

    public function findByEmail(string $email): ?User {
        return $this->repository->findByEmail($email);
    }

    public function findByVerificationToken(string $token): ?User {
        return $this->repository->findByVerificationToken($token);
    }

    public function countByRole(string $role): int {
        return $this->repository->countByRole($role);
    }

    public function countNonAdmins(): int {
        return $this->repository->countNonAdmins();
    }

    public function save(array|User $data, int $id = 0): int {
        return $this->repository->save($data, $id);
    }

    public function updateAddress(int $id, string $address): void {
        $this->repository->updateAddress($id, $address);
    }

    public function delete(int $id): void {
        $this->repository->delete($id);
    }
}
