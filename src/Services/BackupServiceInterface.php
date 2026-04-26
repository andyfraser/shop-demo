<?php
namespace App\Services;

interface BackupServiceInterface {
    public function export(): array;
    public function import(array $file): bool;
}
