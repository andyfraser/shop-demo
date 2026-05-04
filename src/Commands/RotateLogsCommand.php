<?php

namespace App\Commands;

class RotateLogsCommand implements CommandInterface {
    private string $logDir;
    private int $retentionDays;

    public function __construct(string $logDir, int $retentionDays = 30) {
        $this->logDir = $logDir;
        $this->retentionDays = $retentionDays;
    }

    public function getName(): string {
        return 'logs:rotate';
    }

    public function getDescription(): string {
        return 'Rotates all log files and removes old entries.';
    }

    public function getSchedule(): ?string {
        return 'daily';
    }

    public function execute(): int {
        if (!is_dir($this->logDir)) {
            echo "Log directory does not exist: {$this->logDir}\n";
            return 1;
        }

        echo "Processing logs in: {$this->logDir}\n";

        // 1. Rotate active log files
        // We look for files ending in .log that DON'T have a date pattern (e.g., app.log)
        $files = glob($this->logDir . '/*.log');
        $today = date('Y-m-d');

        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip already rotated files (simple check for date-like pattern)
            if (preg_match('/-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
                continue;
            }

            // Skip empty files
            if (filesize($file) === 0) {
                continue;
            }

            $lastModified = filemtime($file);
            $lastModifiedDate = date('Y-m-d', $lastModified);

            $info = pathinfo($file);
            $rotatedFile = sprintf(
                "%s/%s-%s.%s",
                $info['dirname'],
                $info['filename'],
                $lastModifiedDate,
                $info['extension']
            );
            $compressedFile = $rotatedFile . '.gz';

            // Rotate and compress if we haven't already done so for this date.
            if (!file_exists($compressedFile) && !file_exists($rotatedFile)) {
                // We first rename to the rotated name, then compress it.
                // This minimizes the time the original log file is "gone".
                if (rename($file, $rotatedFile)) {
                    $content = file_get_contents($rotatedFile);
                    $gzdata = gzencode($content, 9);
                    if ($gzdata !== false && file_put_contents($compressedFile, $gzdata) !== false) {
                        unlink($rotatedFile);
                        echo "Rotated and compressed: {$filename} -> " . basename($compressedFile) . "\n";
                    } else {
                        echo "Rotated but failed to compress: {$filename}\n";
                    }
                } else {
                    echo "Failed to rotate: {$filename}\n";
                }
            }
        }

        // 2. Cleanup old rotated files
        $allRotatedFiles = glob($this->logDir . '/*-*-*-*.log*');
        $threshold = strtotime("-{$this->retentionDays} days");

        foreach ($allRotatedFiles as $file) {
            // Verify it matches the date pattern YYYY-MM-DD
            if (preg_match('/-\d{4}-\d{2}-\d{2}\.log(\.gz)?$/', basename($file))) {
                if (filemtime($file) < $threshold) {
                    if (unlink($file)) {
                        echo "Deleted old log: " . basename($file) . "\n";
                    } else {
                        echo "Failed to delete: " . basename($file) . "\n";
                    }
                }
            }
        }

        return 0;
    }
}
