<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Core\Database;

class AuthServiceTest extends TestCase {
    private AuthService $auth;
    private \PDO $db;

    public function setUp() {
        $_SESSION = [];
        $this->db = Database::getConnection();
        $settings = new SettingsService($this->db);
        $this->auth = new AuthService($this->db, $settings);
    }

    public function testLoginSetsSession() {
        $user = ['id' => 1, 'name' => 'Test User', 'role' => 'customer'];
        $this->auth->login($user);
        
        $this->assertEquals($user, $_SESSION['user']);
        $this->assertEquals($user, $this->auth->currentUser());
    }

    public function testLogoutClearsSession() {
        $_SESSION['user'] = ['id' => 1];
        $this->auth->logout();
        
        $this->assertFalse(isset($_SESSION['user']));
        $this->assertNull($this->auth->currentUser());
    }

    public function testIsAdmin() {
        $admin = ['id' => 1, 'role' => 'admin'];
        $this->auth->login($admin);
        $this->assertTrue($this->auth->isAdmin());

        $customer = ['id' => 2, 'role' => 'customer'];
        $this->auth->login($customer);
        $this->assertFalse($this->auth->isAdmin());
    }
}
