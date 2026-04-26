<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UserServiceInterface;
use App\Services\UserService;
use App\Models\User;
use App\Core\Database;

class UserServiceTest extends TestCase {
    private UserServiceInterface $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->service = new UserService($this->db, new \Tests\NullLogger());
    }

    public function testFindById() {
        $user = $this->service->findById(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->id);
    }

    public function testFindByEmail() {
        // Assume admin@shop.local exists in seed
        $user = $this->service->findByEmail('admin@shop.local');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('admin@shop.local', $user->email);
    }

    public function testSave() {
        $email = 'newuser' . time() . '@example.com';
        $data = [
            'name'               => 'Test New User',
            'email'              => $email,
            'password_hash'      => 'hash',
            'role'               => 'customer',
            'is_verified'        => 1,
            'verification_token' => null,
            'address'            => 'Test Address',
        ];

        $id = $this->service->save($data);
        $this->assertGreaterThan(0, $id);

        $user = $this->service->findById($id);
        $this->assertEquals('Test New User', $user->name);
        $this->assertEquals($email, $user->email);
        $this->assertTrue($user->isVerified());
    }

    public function testUpdateAddress() {
        $this->service->updateAddress(1, 'New Updated Address');
        $user = $this->service->findById(1);
        $this->assertEquals('New Updated Address', $user->address);
    }
}
