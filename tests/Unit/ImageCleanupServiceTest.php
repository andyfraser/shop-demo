<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageCleanupService;
use Tests\NullLogger;
use PDO;

class ImageCleanupServiceTest extends TestCase {
    private string $testUploadDir;
    private string $testImageDir;
    private PDO $db;
    private ImageCleanupService $service;

    public function setUp(): void {
        $this->testUploadDir = __DIR__ . '/../../public/uploads_test_cleanup/';
        $this->testImageDir = __DIR__ . '/../../public/images_test_cleanup/';
        
        if (!is_dir($this->testUploadDir)) mkdir($this->testUploadDir, 0755, true);
        if (!is_dir($this->testImageDir)) mkdir($this->testImageDir, 0755, true);

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE products (id INTEGER PRIMARY KEY, image TEXT)");

        $this->service = new ImageCleanupService(
            $this->db,
            new NullLogger(),
            $this->testUploadDir,
            $this->testImageDir
        );
    }

    public function tearDown(): void {
        $this->removeDir($this->testUploadDir);
        $this->removeDir($this->testImageDir);
    }

    private function removeDir(string $dir): void {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) $this->removeDir($path);
                else unlink($path);
            }
            rmdir($dir);
        }
    }

    public function testCleanup() {
        // 1. Setup Active Images
        $this->db->exec("INSERT INTO products (image) VALUES ('active.jpg')");
        $this->db->exec("INSERT INTO products (image) VALUES ('active_with_thumbs.png')");
        
        // 2. Create files in uploads
        touch($this->testUploadDir . 'active.jpg'); // Active
        touch($this->testUploadDir . 'active_with_thumbs.png'); // Active
        touch($this->testUploadDir . 'active_with_thumbs_thumb.webp'); // Active (thumb)
        touch($this->testUploadDir . 'active_with_thumbs_large.webp'); // Active (large)
        touch($this->testUploadDir . 'orphaned.jpg'); // Orphaned
        touch($this->testUploadDir . 'orphaned_thumb.webp'); // Orphaned (thumb)
        touch($this->testUploadDir . '.gitkeep'); // Should be ignored
        
        // 3. Create files in images
        touch($this->testImageDir . 'img_active_in_images.jpg'); // We'll add this to DB
        $this->db->exec("INSERT INTO products (image) VALUES ('img_active_in_images.jpg')");
        touch($this->testImageDir . 'img_orphaned_in_images.jpg'); // Orphaned but matches img_ pattern
        touch($this->testImageDir . 'static_asset.svg'); // Should be ignored (no img_ prefix)

        // 4. Run cleanup
        $deleted = $this->service->cleanup();

        // 5. Assertions
        $this->assertCount(3, $deleted); // orphaned.jpg, orphaned_thumb.webp, img_orphaned_in_images.jpg
        
        $this->assertTrue(file_exists($this->testUploadDir . 'active.jpg'));
        $this->assertTrue(file_exists($this->testUploadDir . 'active_with_thumbs.png'));
        $this->assertTrue(file_exists($this->testUploadDir . 'active_with_thumbs_thumb.webp'));
        $this->assertTrue(file_exists($this->testUploadDir . 'active_with_thumbs_large.webp'));
        $this->assertTrue(file_exists($this->testUploadDir . '.gitkeep'));
        
        $this->assertFalse(file_exists($this->testUploadDir . 'orphaned.jpg'));
        $this->assertFalse(file_exists($this->testUploadDir . 'orphaned_thumb.webp'));
        
        $this->assertTrue(file_exists($this->testImageDir . 'img_active_in_images.jpg'));
        $this->assertTrue(file_exists($this->testImageDir . 'static_asset.svg'));
        $this->assertFalse(file_exists($this->testImageDir . 'img_orphaned_in_images.jpg'));
    }
}
