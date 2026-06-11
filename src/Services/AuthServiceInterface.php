<?php
namespace App\Services;

use App\Models\User;

interface AuthServiceInterface {
    public function sessionStart(): void;
    public function currentUser(): ?User;
    public function isAdmin(): bool;
    public function login(array|User $user, bool $remember = false): void;
    public function generateApiTokenForUser(User $user): string;
    public function verifyApiToken(string $token): ?User;
    public function revokeApiToken(string $token): bool;
}
