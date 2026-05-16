<?php
namespace App\Services;

use Psr\Log\LoggerInterface;

class ImageService implements ImageServiceInterface {
    private string $uploadDir;
    private string $baseUrl;

    public function __construct(
        private LoggerInterface $logger,
        ?string $uploadDir = null,
        ?string $baseUrl = null
    ) {
        $this->uploadDir = $uploadDir ?? (__DIR__ . '/../../public/uploads/');
        $this->baseUrl = $baseUrl ?? '/public/uploads/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    public function processUpload(array $file): ?string {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            $this->logger->error("Disallowed file extension: {$ext}");
            return null;
        }

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            $this->logger->error("Disallowed MIME type: {$mimeType}");
            return null;
        }

        $baseName = uniqid('img_', true);
        $originalName = $baseName . '.' . $ext;
        
        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $originalName)) {
            return null;
        }

        $this->generateResized($originalName, $baseName . '_thumb.webp', 400, 400);
        $this->generateResized($originalName, $baseName . '_large.webp', 1200, 1200);

        return $baseName . '.' . $ext;
    }

    public function getUrl(?string $filename, string $size = 'original'): string {
        if (!$filename) return '/public/images/placeholder.svg';
        
        $pathInfo = pathinfo($filename);
        $base = $pathInfo['filename'];
        
        if ($size === 'thumb') {
            $thumbFile = $base . '_thumb.webp';
            if (file_exists($this->uploadDir . $thumbFile)) return $this->baseUrl . $thumbFile;
        } elseif ($size === 'large') {
            $largeFile = $base . '_large.webp';
            if (file_exists($this->uploadDir . $largeFile)) return $this->baseUrl . $largeFile;
        }
        
        // If optimized size not found or original requested, check new uploads dir then old images dir
        if (file_exists($this->uploadDir . $filename)) {
            return $this->baseUrl . $filename;
        }
        
        // Fallback for old images
        $oldPath = __DIR__ . '/../../public/images/' . $filename;
        if (file_exists($oldPath)) {
            return '/public/images/' . $filename;
        }
        
        return '/public/images/placeholder.svg';
    }

    public function delete(?string $filename): void {
        if (!$filename) return;
        
        $pathInfo = pathinfo($filename);
        $base = $pathInfo['filename'];
        
        $filesToDelete = [
            $filename,
            $base . '_thumb.webp',
            $base . '_large.webp'
        ];

        foreach ($filesToDelete as $f) {
            $fullPath = $this->uploadDir . $f;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    private function generateResized(string $sourceFile, string $targetName, int $maxWidth, int $maxHeight): bool {
        $sourcePath = $this->uploadDir . $sourceFile;
        if (!file_exists($sourcePath)) return false;

        $info = getimagesize($sourcePath);
        if (!$info) return false;

        [$width, $height, $type] = $info;
        
        $srcImage = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF  => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default        => null
        };

        if (!$srcImage) return false;

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        if ($ratio >= 1.0) {
            $newWidth = $width;
            $newHeight = $height;
        } else {
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/GIF/WebP
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || $type === IMAGETYPE_WEBP) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $result = false;
        if (function_exists('imagewebp')) {
            $result = imagewebp($dstImage, $this->uploadDir . $targetName, 80);
        } else {
            // Fallback to JPEG if WebP not supported
            $result = imagejpeg($dstImage, $this->uploadDir . str_replace('.webp', '.jpg', $targetName), 80);
        }

        return $result;
    }
}
