<?php
namespace App\Services;

interface ImageServiceInterface {
    /**
     * Process an uploaded file, generating optimized versions.
     * Returns the base filename.
     */
    public function processUpload(array $file): ?string;

    /**
     * Get the URL for a specific size of an image.
     * Sizes: 'thumb', 'medium', 'large', 'original'
     */
    public function getUrl(?string $filename, string $size = 'original'): string;

    /**
     * Delete all versions of an image.
     */
    public function delete(?string $filename): void;
}
