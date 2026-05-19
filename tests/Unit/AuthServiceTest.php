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
        $logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        $eventDispatcher = new \Tests\NullEventDispatcher();
        $settingsRepo = new \App\Repositories\SettingsRepository($this->db, $logger);
        $settings = new SettingsService($settingsRepo, $logger, $cache, $eventDispatcher);
        $authRepo = new \App\Repositories\AuthRepository($this->db, $logger);
        $this->auth = new AuthService($authRepo, $settings, $logger, $eventDispatcher);
    }

    public function testLoginSetsSession() {
        // Use a unique ID and Email to avoid conflicts
        $id = 1000 + rand(1, 1000);
        $email = "test{$id}@example.com";
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role) VALUES (?, 'Test User', ?, 'password', 'customer')")
                 ->execute([$id, $email]);

        $user = ['id' => $id, 'name' => 'Test User', 'role' => 'customer'];
        $this->auth->login($user);
        
        $this->assertInstanceOf(\App\Models\User::class, $_SESSION['user']);
        $this->assertEquals('Test User', $this->auth->currentUser()->name);
    }

    public function testLogoutClearsSession() {
        $_SESSION['user'] = new \App\Models\User(new \Tests\NullLogger());
        $this->auth->logout();
        
        $this->assertFalse(isset($_SESSION['user']));
        $this->assertNull($this->auth->currentUser());
    }

    public function testIsAdmin() {
        $id1 = 2000 + rand(1, 1000);
        $id2 = 3000 + rand(1, 1000);
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role) VALUES (?, 'Admin', ?, 'password', 'admin')")
                 ->execute([$id1, "admin{$id1}@example.com"]);
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role) VALUES (?, 'Customer', ?, 'password', 'customer')")
                 ->execute([$id2, "cust{$id2}@example.com"]);

        $admin = ['id' => $id1, 'role' => 'admin'];
        $this->auth->login($admin);
        $this->assertTrue($this->auth->isAdmin());

        $customer = ['id' => $id2, 'role' => 'customer'];
        $this->auth->login($customer);
        $this->assertFalse($this->auth->isAdmin());
    }
}
