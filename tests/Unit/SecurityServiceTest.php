<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SecurityService;

class SecurityServiceTest extends TestCase {
    public function setUp() {
        $_SESSION = [];
    }

    public function testCsrfTokenGeneration() {
        $token = SecurityService::csrfToken();
        $this->assertTrue(strlen($token) === 64); // 32 bytes in hex
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testCsrfTokenPersistence() {
        $token = SecurityService::csrfToken();
        $token2 = SecurityService::csrfToken();
        $this->assertEquals($token, $token2);
    }

    public function testCsrfField() {
        $token = SecurityService::csrfToken();
        $field = SecurityService::csrfField();
        $this->assertTrue(strpos($field, 'type="hidden"') !== false);
        $this->assertTrue(strpos($field, 'name="csrf_token"') !== false);
        $this->assertTrue(strpos($field, 'value="' . $token . '"') !== false);
    }
}
