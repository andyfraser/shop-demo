<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Commands\RememberTokenCleanupCommand;
use App\Core\Database;
use App\Repositories\AuthRepository;
use Tests\NullLogger;
use PDO;

class RememberTokenCleanupCommandTest extends TestCase {
    private PDO $db;

    public function setUp(): void {
        $this->db = Database::getConnection();
        // Clean tokens table
        $this->db->exec("DELETE FROM remember_tokens");
    }

    public function testCleanupRemovesExpiredTokens(): void {
        $now = time();
        $expiredTime = $now - 3600;
        $futureTime = $now + 3600;

        // Insert expired token
        $stmt = $this->db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([1, 'expired-token', $expiredTime]);

        // Insert active token
        $stmt->execute([1, 'active-token', $futureTime]);

        $authRepository = new AuthRepository($this->db, new NullLogger());
        $command = new RememberTokenCleanupCommand($authRepository, new NullLogger());

        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Deleted 1 expired remember tokens', $output);

        // Verify database state
        $stmt = $this->db->prepare("SELECT token FROM remember_tokens ORDER BY expires_at DESC");
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->assertCount(1, $tokens);
        $this->assertEquals('active-token', $tokens[0]);
    }
}
