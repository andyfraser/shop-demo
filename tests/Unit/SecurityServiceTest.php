<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SecurityServiceInterface;
use App\Services\SecurityService;

use App\Core\Database;

class SecurityServiceTest extends TestCase {
    private SecurityServiceInterface $security;

    public function setUp() {
        $_SESSION = [];
        $logger = new \Tests\NullLogger();
        $db = Database::getConnection();
        $repository = new \App\Repositories\SecurityRepository($db);
        $this->security = new SecurityService($repository, $logger);
    }

    public function testCsrfTokenGeneration() {
        $token = $this->security->csrfToken();
        $this->assertTrue(strlen($token) === 64); // 32 bytes in hex
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testCsrfTokenPersistence() {
        $token = $this->security->csrfToken();
        $token2 = $this->security->csrfToken();
        $this->assertEquals($token, $token2);
    }

    public function testCsrfField() {
        $token = $this->security->csrfToken();
        $field = $this->security->csrfField();
        $this->assertTrue(strpos($field, 'type="hidden"') !== false);
        $this->assertTrue(strpos($field, 'name="csrf_token"') !== false);
        $this->assertTrue(strpos($field, 'value="' . $token . '"') !== false);
    }

    public function testValidateCsrf() {
        $token = $this->security->csrfToken();
        $this->assertTrue($this->security->validateCsrf($token));
        $this->assertFalse($this->security->validateCsrf('wrong-token'));
        $this->assertFalse($this->security->validateCsrf(''));
        $this->assertFalse($this->security->validateCsrf(null));
    }

    public function testIsRateLimited() {
        $ip = '127.0.0.1';
        $action = 'test-action';
        
        // Not limited initially
        $this->assertFalse($this->security->isRateLimited($action, $ip, 3, 60));
        
        // Record 3 attempts
        $this->security->recordRateLimit($action, $ip);
        $this->security->recordRateLimit($action, $ip);
        $this->security->recordRateLimit($action, $ip);
        
        // Now should be limited
        $this->assertTrue($this->security->isRateLimited($action, $ip, 3, 60));
        
        // Clear and should not be limited
        $this->security->clearRateLimit($action, $ip);
        $this->assertFalse($this->security->isRateLimited($action, $ip, 3, 60));
    }
}
