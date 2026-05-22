<?php

namespace App\Commands;

/**
 * Command to stop the built-in PHP development server.
 */
class ServeStopCommand implements CommandInterface {
    public function getName(): string {
        return 'serve:stop';
    }

    public function getDescription(): string {
        return 'Stops the built-in PHP development server.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        $pidFile = __DIR__ . '/../../storage/server.pid';

        if (!file_exists($pidFile)) {
            echo "Server is not running (no PID file found).\n";
            return 0;
        }

        $pid = (int)file_get_contents($pidFile);
        
        if ($pid > 0) {
            echo "Stopping server (PID: {$pid})...\n";
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /PID {$pid} 2>&1");
            } else {
                exec("kill {$pid} 2>&1");
            }
            
            // Give it a moment to stop
            usleep(500000);
            
            if ($this->isRunning($pid)) {
                echo "Failed to stop server (PID: {$pid} still active).\n";
                return 1;
            }

            unlink($pidFile);
            echo "Server stopped.\n";
            return 0;
        }

        echo "Invalid PID in PID file. Cleaning up.\n";
        unlink($pidFile);
        return 1;
    }

    private function isRunning(int $pid): bool {
        if (function_exists('posix_getpgid')) {
            return posix_getpgid($pid) !== false;
        }
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("tasklist /FI \"PID eq {$pid}\"");
            return $output && strpos($output, (string)$pid) !== false;
        }
        
        $output = shell_exec("ps -p {$pid}");
        return $output && strpos($output, (string)$pid) !== false;
    }
}
