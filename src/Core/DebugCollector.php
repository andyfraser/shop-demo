<?php

namespace App\Core;

class DebugCollector {
    private float $startTime;
    private int $queryCount = 0;
    private array $queries = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    private array $cacheOperations = [];
    private array $logs = [];
    private array $milestones = [];
    private float $slowQueryThreshold = 10.0; // in milliseconds
    private ?array $matchedRoute = null;
    private static ?DebugCollector $instance = null;
    private static ?bool $forceEnabled = null;

    public function __construct() {
        $this->startTime = microtime(true);
        self::$instance = $this;
        $this->addMilestone('Boot');
    }

    public static function getInstance(): DebugCollector {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function forceEnable(?bool $enabled): void {
        self::$forceEnabled = $enabled;
    }

    private function isDebugEnabled(): bool {
        if (self::$forceEnabled !== null) {
            return self::$forceEnabled;
        }
        return defined('DEBUG_MODE') && DEBUG_MODE;
    }

    public function logQuery(string $sql, array $params = [], float $duration = 0): void {
        if (!$this->isDebugEnabled()) {
            return;
        }
        $this->queryCount++;
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration
        ];
    }

    public function logCache(string $operation, string $key, bool $isHit): void {
        if (!$this->isDebugEnabled()) {
            return;
        }
        if (strtolower($operation) === 'get') {
            if ($isHit) {
                $this->cacheHits++;
            } else {
                $this->cacheMisses++;
            }
        }
        $this->cacheOperations[] = [
            'operation' => $operation,
            'key' => $key,
            'status' => $isHit ? 'hit' : 'miss',
            'time' => microtime(true) - $this->startTime
        ];
    }

    public function logCacheHit(string $key = ''): void {
        $this->logCache('get', $key ?: 'unknown', true);
    }

    public function logCacheMiss(string $key = ''): void {
        $this->logCache('get', $key ?: 'unknown', false);
    }

    public function logMessage(string $level, string $message, array $context = []): void {
        if (!$this->isDebugEnabled()) {
            return;
        }
        $log = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => microtime(true) - $this->startTime
        ];
        $this->logs[] = $log;

        // Persist to session for redirect support if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['__debug_redirect_logs'])) {
                $_SESSION['__debug_redirect_logs'] = [];
            }
            $_SESSION['__debug_redirect_logs'][] = $log;
        }
    }

    public function addMilestone(string $name): void {
        if (!$this->isDebugEnabled()) {
            return;
        }
        $this->milestones[] = [
            'name' => $name,
            'time' => microtime(true) - $this->startTime
        ];
    }

    public function getCacheHits(): int {
        return $this->cacheHits;
    }

    public function getCacheMisses(): int {
        return $this->cacheMisses;
    }

    public function getCacheOperations(): array {
        return $this->cacheOperations;
    }

    public function getLogs(): array {
        return $this->logs;
    }

    public function getMilestones(): array {
        return $this->milestones;
    }

    public function getQueryCount(): int {
        return $this->queryCount;
    }

    public function getQueries(): array {
        return $this->queries;
    }

    public function setSlowQueryThreshold(float $threshold): void {
        $this->slowQueryThreshold = $threshold;
    }

    public function getSlowQueryThreshold(): float {
        return $this->slowQueryThreshold;
    }

    public function getExecutionTime(): float {
        return microtime(true) - $this->startTime;
    }

    public function getMemoryPeak(): float {
        return memory_get_peak_usage(true) / 1024 / 1024; // MB
    }

    public function setMatchedRoute(?array $route): void {
        $this->matchedRoute = $route;
    }

    public function getMatchedRoute(): ?array {
        return $this->matchedRoute;
    }
}

