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
        $logger = new \App\Core\FileLogger(__DIR__ . '/../../test_app.log');
        $this->security = new SecurityService(Database::getConnection(), $logger);
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
}
