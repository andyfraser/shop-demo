<?php

namespace App\Repositories;

interface JobRepositoryInterface {
    public function create(array $data): int;
    public function findPending(int $limit = 10): array;
    public function update(int $id, array $data): bool;
    public function claim(int $id, string $startedAt, int $attempts): bool;
    public function claimNextPending(string $startedAt): ?array;
    public function deleteByStatusAndAge(string $status, int $hours): int;
}
