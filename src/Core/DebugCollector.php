<?php

namespace App\Core;

class DebugCollector {
    private float $startTime;
    private int $queryCount = 0;
    private array $queries = [];
    private static ?DebugCollector $instance = null;

    public function __construct() {
        $this->startTime = microtime(true);
        self::$instance = $this;
    }

    public static function getInstance(): DebugCollector {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function logQuery(string $sql, array $params = [], float $duration = 0): void {
        $this->queryCount++;
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration
        ];
    }

    public function getQueryCount(): int {
        return $this->queryCount;
    }

    public function getQueries(): array {
        return $this->queries;
    }

    public function getExecutionTime(): float {
        return microtime(true) - $this->startTime;
    }

    public function getMemoryPeak(): float {
        return memory_get_peak_usage(true) / 1024 / 1024; // MB
    }
}
