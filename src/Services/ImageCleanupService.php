<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use PDO;

class ImageCleanupService implements ImageCleanupServiceInterface {
    private string $uploadDir;
    private string $oldImagesDir;

    public function __construct(
        private PDO $db,
        private LoggerInterface $logger,
        ?string $uploadDir = null,
        ?string $oldImagesDir = null
    ) {
        $this->uploadDir = $uploadDir ?? (__DIR__ . '/../../public/uploads/');
        $this->oldImagesDir = $oldImagesDir ?? (__DIR__ . '/../../public/images/');
    }

    public function cleanup(): array {
        $deletedFiles = [];

        // 1. Get all active images from the database
        $stmt = $this->db->query("SELECT DISTINCT image FROM products WHERE image IS NOT NULL AND image != ''");
        $activeImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $activeBases = [];
        foreach ($activeImages as $img) {
            $activeBases[] = pathinfo($img, PATHINFO_FILENAME);
        }

        // 2. Clean up public/uploads
        $deletedFiles = array_merge($deletedFiles, $this->scanAndCleanup($this->uploadDir, $activeImages, $activeBases));

        // 3. Clean up public/images (Be careful here!)
        // We only want to delete files that look like product images (e.g. start with img_)
        $deletedFiles = array_merge($deletedFiles, $this->scanAndCleanup($this->oldImagesDir, $activeImages, $activeBases, true));

        $this->logger->info("Image cleanup completed. Deleted {count} files.", ['count' => count($deletedFiles)]);

        return $deletedFiles;
    }

    private function scanAndCleanup(string $dir, array $activeImages, array $activeBases, bool $onlyProductPatterns = false): array {
        $deleted = [];
        if (!is_dir($dir)) return [];

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
            
            $fullPath = $dir . $file;
            if (is_dir($fullPath)) continue;

            // Protection for static assets in public/images
            if ($onlyProductPatterns) {
                if (!str_starts_with($file, 'img_')) continue;
            }

            $pathInfo = pathinfo($file);
            $base = $pathInfo['filename'];
            $extension = $pathInfo['extension'] ?? '';

            // Handle thumbnails: img_123_thumb.webp -> base is img_123
            $isThumbnail = false;
            if (str_ends_with($base, '_thumb') || str_ends_with($base, '_large')) {
                $actualBase = substr($base, 0, strrpos($base, '_'));
                $isThumbnail = true;
            } else {
                $actualBase = $base;
            }

            $isOrphaned = false;
            if ($isThumbnail) {
                if (!in_array($actualBase, $activeBases)) {
                    $isOrphaned = true;
                }
            } else {
                // Main image file
                if (!in_array($file, $activeImages)) {
                    $isOrphaned = true;
                }
            }

            if ($isOrphaned) {
                if (unlink($fullPath)) {
                    $deleted[] = $fullPath;
                    $this->logger->debug("Deleted orphaned image: {path}", ['path' => $fullPath]);
                }
            }
        }

        return $deleted;
    }
}
