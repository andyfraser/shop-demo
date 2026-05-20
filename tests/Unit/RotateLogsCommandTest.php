<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Commands\RotateLogsCommand;

class RotateLogsCommandTest extends TestCase {
    private string $testLogDir = __DIR__ . '/../../test_logs';

    public function setUp(): void {
        if (!is_dir($this->testLogDir)) {
            mkdir($this->testLogDir, 0755, true);
        }
        $this->cleanDir();
    }

    private function cleanDir(): void {
        $files = glob($this->testLogDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }

    public function testRotation() {
        $logFile = $this->testLogDir . '/app.log';
        $content = 'Yesterday log content';
        file_put_contents($logFile, $content);
        
        $yesterday = strtotime('-1 day');
        touch($logFile, $yesterday);
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $expectedRotatedFile = $this->testLogDir . '/app-' . date('Y-m-d', $yesterday) . '.log.gz';
        $this->assertTrue(file_exists($expectedRotatedFile), 'Log file should be rotated and compressed');
        $this->assertFalse(file_exists($logFile), 'Original log file should be moved');
        
        $decompressed = gzdecode(file_get_contents($expectedRotatedFile));
        $this->assertStringContainsString($content, $decompressed);
    }

    public function testCleanup() {
        $oldFile = $this->testLogDir . '/app-2000-01-01.log.gz';
        file_put_contents($oldFile, gzencode('Ancient content'));
        touch($oldFile, strtotime('-40 days')); // Older than 30 days
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $this->assertFalse(file_exists($oldFile), 'Old compressed rotated file should be deleted');
    }

    public function testHandlesMultipleLogFiles() {
        $log1 = $this->testLogDir . '/web.log';
        $log2 = $this->testLogDir . '/db.log';
        
        file_put_contents($log1, 'web');
        file_put_contents($log2, 'db');
        
        $yesterday = strtotime('-1 day');
        touch($log1, $yesterday);
        touch($log2, $yesterday);
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $this->assertTrue(file_exists($this->testLogDir . '/web-' . date('Y-m-d', $yesterday) . '.log.gz'));
        $this->assertTrue(file_exists($this->testLogDir . '/db-' . date('Y-m-d', $yesterday) . '.log.gz'));
    }

    public function testRotatesLogModifiedToday() {
        $logFile = $this->testLogDir . '/active.log';
        $content = 'Today log content';
        file_put_contents($logFile, $content);
        
        // Ensure mtime is today
        touch($logFile, time());
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $expectedRotatedFile = $this->testLogDir . '/active-' . date('Y-m-d') . '.log.gz';
        $this->assertTrue(file_exists($expectedRotatedFile), 'Active log file should be rotated and compressed even if modified today');
        $this->assertFalse(file_exists($logFile), 'Original active log file should be moved');
        
        $decompressed = gzdecode(file_get_contents($expectedRotatedFile));
        $this->assertStringContainsString($content, $decompressed);
    }

    public function testDoesNotRotateEmptyFiles() {
        $logFile = $this->testLogDir . '/empty.log';
        file_put_contents($logFile, '');
        
        $yesterday = strtotime('-1 day');
        touch($logFile, $yesterday);
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $expectedRotatedFile = $this->testLogDir . '/empty-' . date('Y-m-d', $yesterday) . '.log';
        $this->assertFalse(file_exists($expectedRotatedFile), 'Empty log file should not be rotated');
        $this->assertTrue(file_exists($logFile), 'Original empty log file should remain');
    }

    public function testCompressesOrphanedLogs() {
        $rotatedFile = $this->testLogDir . '/orphaned-2026-05-20.log';
        file_put_contents($rotatedFile, 'Some content');
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $this->assertTrue(file_exists($rotatedFile . '.gz'), 'Orphaned log should be compressed');
        $this->assertFalse(file_exists($rotatedFile), 'Original orphaned log should be removed');
        
        $decompressed = gzdecode(file_get_contents($rotatedFile . '.gz'));
        $this->assertStringContainsString('Some content', $decompressed);
    }

    public function tearDown(): void {
        $this->cleanDir();
        if (is_dir($this->testLogDir)) {
            rmdir($this->testLogDir);
        }
    }
}
