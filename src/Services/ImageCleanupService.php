<?php

namespace App\Services;

use App\Repositories\ImageRepositoryInterface;
use Psr\Log\LoggerInterface;

class ImageCleanupService implements ImageCleanupServiceInterface {
    private string $uploadDir;
    private string $oldImagesDir;

    public function __construct(
        private ImageRepositoryInterface $repository,
        private LoggerInterface $logger,
        ?string $uploadDir = null,
        ?string $oldImagesDir = null
    ) {
        $this->uploadDir = $uploadDir ?? (__DIR__ . '/../../public/uploads/');
        $this->oldImagesDir = $oldImagesDir ?? (__DIR__ . '/../../public/images/');
    }

    public function cleanup(): array {
        $deletedFiles = [];

        // 1. Get all active images and icons from the database
        $activeImages = $this->repository->getActiveImageNames();
        
        $activeBases = [];
        foreach ($activeImages as $img) {
            $activeBases[] = strtolower(pathinfo($img, PATHINFO_FILENAME));
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

            $fileLower = strtolower($file);

            // Protection for static assets in public/images
            if ($onlyProductPatterns) {
                if (!str_starts_with($fileLower, 'img_')) continue;
            }

            $pathInfo = pathinfo($fileLower);
            $base = $pathInfo['filename'];
            $extension = $pathInfo['extension'] ?? '';

            // Handle thumbnails: img_123_thumb.webp -> base is img_123
            $isThumbnail = false;
            if (str_ends_with($base, '_thumb') || str_ends_with($base, '_medium') || str_ends_with($base, '_large')) {
                $actualBase = substr($base, 0, strrpos($base, '_'));
                $isThumbnail = true;
            } else {
                $actualBase = $base;
            }

            $isOrphaned = false;
            if (in_array($fileLower, $activeImages)) {
                $isOrphaned = false;
            } elseif ($isThumbnail) {
                if (!in_array($actualBase, $activeBases)) {
                    $isOrphaned = true;
                }
            } else {
                $isOrphaned = true;
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
