<?php

namespace App\Commands;

use App\Services\ImageCleanupServiceInterface;

class ImageCleanupCommand implements CommandInterface {
    public function __construct(
        private ImageCleanupServiceInterface $cleanupService
    ) {}

    public function getName(): string {
        return 'images:cleanup';
    }

    public function getDescription(): string {
        return 'Removes orphaned images from the upload directory.';
    }

    public function getSchedule(): ?string {
        return 'weekly';
    }

    public function execute(): int {
        echo "Starting image cleanup...\n";
        
        $deleted = $this->cleanupService->cleanup();
        
        foreach ($deleted as $file) {
            echo "Deleted: " . basename($file) . "\n";
        }
        
        echo "Cleanup complete. Total files deleted: " . count($deleted) . "\n";
        
        return 0;
    }
}
