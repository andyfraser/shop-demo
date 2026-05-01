<?php

namespace App\Core;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class FileLogger extends AbstractLogger {
    private int $retentionDays = 30;

    public function __construct(
        private string $logFile,
        private bool $isDebug = false
    ) {
        $this->ensureDirectoryExists();
    }

    public function log($level, $message, array $context = []) {
        if ($level === LogLevel::DEBUG && !$this->isDebug) {
            return;
        }

        $message = $this->interpolate((string)$message, $context);
        $date = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] %s: %s" . PHP_EOL, $date, strtoupper($level), $message);

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    private function ensureDirectoryExists(): void {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    private function interpolate(string $message, array $context = []): string {
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }
}
