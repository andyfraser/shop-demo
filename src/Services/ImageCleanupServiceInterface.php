<?php

namespace App\Services;

interface ImageCleanupServiceInterface {
    /**
     * Remove orphaned images from the upload directories.
     * 
     * @return array List of deleted files.
     */
    public function cleanup(): array;
}
