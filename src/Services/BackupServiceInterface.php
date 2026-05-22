<?php
namespace App\Services;

interface BackupServiceInterface {
    public function export(?callable $onProgress = null): array;
    public function import(array $file, ?callable $onProgress = null): bool;
}
