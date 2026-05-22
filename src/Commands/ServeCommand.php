<?php

namespace App\Commands;

use App\Services\SettingsServiceInterface;

/**
 * Command to start the built-in PHP development server in the background.
 */
class ServeCommand implements CommandInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService
    ) {}

    public function getName(): string {
        return 'serve';
    }

    public function getDescription(): string {
        return 'Starts the built-in PHP development server in the background.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        $host = $this->getMachineIp();
        $port = (int)($this->settingsService->get('server_port') ?: 8000);
        $publicDir = realpath(__DIR__ . '/../../public');
        $pidFile = __DIR__ . '/../../storage/server.pid';

        if (!$publicDir) {
            echo "Error: Could not find 'public' directory.\n";
            return 1;
        }

        if (file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);
            if ($this->isRunning($pid)) {
                echo "Server is already running on http://{$host}:{$port} (PID: {$pid}).\n";
                return 0;
            }
            unlink($pidFile);
        }

        echo "Starting server on http://{$host}:{$port}...\n";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = sprintf(
                'start /B php -S %s:%d -t %s > NUL 2>&1',
                $host,
                $port,
                escapeshellarg($publicDir)
            );
            pclose(popen($command, "r"));
            // For Windows, we'll need a different way to find the PID if we want to store it accurately
            // For now, let's focus on Unix/macOS as that's the current environment
            $pid = 0; 
        } else {
            $command = sprintf(
                'nohup php -S %s:%d -t %s > /dev/null 2>&1 & echo $!',
                $host,
                $port,
                escapeshellarg($publicDir)
            );
            $pid = (int)trim(shell_exec($command));
        }

        if ($pid > 0) {
            file_put_contents($pidFile, $pid);
            
            // Wait a moment and check if it's still running
            usleep(500000); 
            if ($this->isRunning($pid)) {
                echo "Server started successfully (PID: {$pid}).\n";
                return 0;
            }
            
            unlink($pidFile);
            echo "Server started but died immediately. Check if port {$port} is already in use.\n";
            return 1;
        }

        echo "Failed to start server.\n";
        return 1;
    }

    private function isRunning(int $pid): bool {
        if (function_exists('posix_getpgid')) {
            return posix_getpgid($pid) !== false;
        }
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("tasklist /FI \"PID eq {$pid}\"");
            return strpos($output, (string)$pid) !== false;
        }
        
        $output = shell_exec("ps -p {$pid}");
        return strpos($output, (string)$pid) !== false;
    }

    private function getMachineIp(): string {
        $ip = '127.0.0.1';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("ipconfig");
            if ($output && preg_match('/IPv4 Address.*: ([\d\.]+)/', $output, $matches)) {
                $ip = $matches[1];
            }
        } else {
            // macOS / Linux
            // Try hostname -I (Linux) first, then ifconfig (macOS/Linux)
            $output = shell_exec("hostname -I 2>/dev/null");
            if ($output) {
                $ips = explode(' ', trim($output));
                $ip = $ips[0];
            } else {
                $output = shell_exec("ifconfig 2>/dev/null | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -n 1");
                if ($output) {
                    $ip = trim($output);
                }
            }
        }
        
        return $ip ?: '0.0.0.0';
    }
}
