<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\FileLogger;

class RecoveryLogRotationTest extends TestCase {
    private string $testLog = __DIR__ . '/../../test_app.log';
    private string $recoveryLog = __DIR__ . '/../../test_recovery.log';

    public function setUp(): void {
        foreach ([$this->testLog, $this->recoveryLog] as $f) {
            if (file_exists($f)) unlink($f);
        }
    }

    public function testRecoveryLogRotation() {
        // Create a fake recovery.log
        file_put_contents($this->recoveryLog, "Recovery data from yesterday");
        $yesterday = strtotime('-1 day');
        touch($this->recoveryLog, $yesterday);

        // Create main log from yesterday too to trigger rotation
        file_put_contents($this->testLog, "Main data from yesterday");
        touch($this->testLog, $yesterday);

        // This logger instance is configured for test_app.log
        // It should also look for recovery.log in the same dir if we name it correctly in the code.
        // Wait, my code looks for dirname($this->logFile) . '/recovery.log'.
        // So I should name it recovery.log in the test dir.
        
        $logDir = dirname($this->testLog);
        $realRecoveryLog = $logDir . '/recovery.log';
        if (file_exists($realRecoveryLog)) unlink($realRecoveryLog);
        file_put_contents($realRecoveryLog, "Real recovery data");
        touch($realRecoveryLog, $yesterday);

        $logger = new FileLogger($this->testLog);
        $logger->info('Trigger rotation');

        $rotatedRecovery = $logDir . '/recovery-' . date('Y-m-d', $yesterday) . '.log';
        
        $this->assertTrue(file_exists($rotatedRecovery), 'Recovery log should be rotated');
        $this->assertFalse(file_exists($realRecoveryLog), 'Original recovery log should be moved/renamed');
        
        // Cleanup
        if (file_exists($rotatedRecovery)) unlink($rotatedRecovery);
        if (file_exists($realRecoveryLog)) unlink($realRecoveryLog);
    }

    public function tearDown(): void {
        foreach ([$this->testLog, $this->recoveryLog] as $f) {
            if (file_exists($f)) unlink($f);
        }
        $realRecoveryLog = dirname($this->testLog) . '/recovery.log';
        if (file_exists($realRecoveryLog)) unlink($realRecoveryLog);
    }
}
