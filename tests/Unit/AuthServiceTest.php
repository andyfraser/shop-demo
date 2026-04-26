<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthServiceInterface;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Core\Database;

class AuthServiceTest extends TestCase {
    private AuthServiceInterface $auth;
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
        
        $this->assertInstanceOf(\App\Models\User::class, $_SESSION['user']);
        $this->assertEquals('Test User', $this->auth->currentUser()->name);
    }

    public function testLogoutClearsSession() {
        $_SESSION['user'] = new \App\Models\User();
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
