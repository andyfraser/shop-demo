<?php

namespace App\Commands;

use Psr\Log\LoggerInterface;

class RotateLogsCommand implements CommandInterface {
    public function __construct(
        private string $logDir,
        private int $retentionDays = 30,
        private ?LoggerInterface $logger = null
    ) {}

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
            if ($this->logger) {
                $this->logger->error("Log rotation failed: directory {dir} does not exist.", ['dir' => $this->logDir]);
            }
            return 1;
        }

        echo "Processing logs in: {$this->logDir}\n";
        if ($this->logger) {
            $this->logger->info("Starting log rotation in {dir}", ['dir' => $this->logDir]);
        }

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
                        if ($this->logger) {
                            $this->logger->info("Rotated and compressed log file: {filename}", ['filename' => $filename]);
                        }
                    } else {
                        echo "Rotated but failed to compress: {$filename}\n";
                        if ($this->logger) {
                            $this->logger->warning("Rotated but failed to compress log file: {filename}", ['filename' => $filename]);
                        }
                    }
                } else {
                    echo "Failed to rotate: {$filename}\n";
                    if ($this->logger) {
                        $this->logger->error("Failed to rotate log file: {filename}", ['filename' => $filename]);
                    }
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
                    $basename = basename($file);
                    if (unlink($file)) {
                        echo "Deleted old log: " . $basename . "\n";
                        if ($this->logger) {
                            $this->logger->info("Deleted old rotated log file: {filename}", ['filename' => $basename]);
                        }
                    } else {
                        echo "Failed to delete: " . $basename . "\n";
                        if ($this->logger) {
                            $this->logger->error("Failed to delete old rotated log file: {filename}", ['filename' => $basename]);
                        }
                    }
                }
            }
        }

        return 0;
    }
}
