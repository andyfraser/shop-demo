<?php

namespace Tests;

class AssertionFailedException extends \Exception {}

abstract class TestCase {
    protected int $assertions = 0;
    protected ?string $expectedException = null;

    public function getAssertionCount(): int {
        return $this->assertions;
    }

    public function expectException(string $className): void {
        $this->expectedException = $className;
    }

    public function getExpectedException(): ?string {
        return $this->expectedException;
    }

    public function clearExpectedException(): void {
        $this->expectedException = null;
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

    protected function assertNull($actual, string $message = ''): void {
        $this->assertions++;
        if ($actual !== null) {
            $msg = $message ?: "Expected null, but got " . var_export($actual, true);
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertNotNull($actual, string $message = ''): void {
        $this->assertions++;
        if ($actual === null) {
            $msg = $message ?: "Expected not null, but got null";
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertSame($expected, $actual, string $message = ''): void {
        $this->assertions++;
        if ($expected !== $actual) {
            $msg = $message ?: "Expected same " . var_export($expected, true) . ", but got " . var_export($actual, true);
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) {
            $msg = $message ?: "Expected string to contain \"$needle\", but it was not found.";
            throw new AssertionFailedException($msg);
        }
    }

    protected function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void {
        $this->assertions++;
        if (strpos($haystack, $needle) !== false) {
            $msg = $message ?: "Expected string NOT to contain \"$needle\", but it was found.";
            throw new AssertionFailedException($msg);
        }
    }
}
