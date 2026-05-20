<?php

namespace App\Core\Events;

/**
 * Marker interface for listeners that should be executed asynchronously.
 */
interface ShouldQueue {
    /**
     * Get the maximum number of times the job may be attempted.
     */
    public function getTries(): int;

    /**
     * Get the number of minutes to wait before retrying a failed job.
     */
    public function getRetryDelay(): int;

    /**
     * Determine if the retry delay should increase exponentially.
     */
    public function useExponentialBackoff(): bool;
}
