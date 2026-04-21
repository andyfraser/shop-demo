<?php

namespace Tests;

class AssertionFailedException extends \Exception {}

abstract class TestCase {
    protected int $assertions = 0;

    public function getAssertionCount(): int {
        return $this->assertions;
    }

    protected function assertEquals($expected, $actual, string $message = ''): void {
        $this->assertions++;
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . ", but got " . var_export($actual, true);
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertTrue($condition, string $message = ''): void {
        $this->assertions++;
        if ($condition !== true) {
            $msg = $message ?: "Expected true, but got " . var_export($condition, true);
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertFalse($condition, string $message = ''): void {
        $this->assertions++;
        if ($condition !== false) {
            $msg = $message ?: "Expected false, but got " . var_export($condition, true);
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertCount(int $expectedCount, $haystack, string $message = ''): void {
        $this->assertions++;
        $actualCount = count($haystack);
        if ($expectedCount !== $actualCount) {
            $msg = $message ?: "Expected count $expectedCount, but got $actualCount";
            throw new AssertionFailedException($msg);
        }
    }
}
