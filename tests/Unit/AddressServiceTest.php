<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AddressService;
use App\Core\Database;

class AddressServiceTest extends TestCase {
    private AddressService $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->service = new AddressService($this->db, new \Tests\NullLogger());
        
        // Clean up user_addresses for test user (assume ID 1 is admin/test user)
        $this->db->prepare("DELETE FROM user_addresses WHERE user_id = 1")->execute();
    }

    public function testSaveAndGet() {
        $data = [
            'label' => 'Home',
            'name' => 'John Doe',
            'address' => '123 Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'country' => 'UK',
            'is_default' => 0
        ];

        $id = $this->service->save(1, $data);
        $this->assertGreaterThan(0, $id);

        $addresses = $this->service->getByUserId(1);
        $this->assertCount(1, $addresses);
        $this->assertEquals('Home', $addresses[0]->label);
        $this->assertEquals(0, $addresses[0]->is_default);
    }

    public function testSetDefault() {
        $data1 = [
            'label' => 'Address 1',
            'name' => 'John Doe',
            'address' => '123 Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'country' => 'UK',
            'is_default' => 0
        ];
        $id1 = $this->service->save(1, $data1);

        $data2 = [
            'label' => 'Address 2',
            'name' => 'John Doe',
            'address' => '456 Avenue',
            'city' => 'Manchester',
            'postcode' => 'M1 1AA',
            'country' => 'UK',
            'is_default' => 1
        ];
        $id2 = $this->service->save(1, $data2);

        $addresses = $this->service->getByUserId(1);
        $this->assertCount(2, $addresses);

        // Address 2 should be default
        $addr2 = $this->service->findById($id2);
        $this->assertEquals(1, $addr2->is_default);

        // Address 1 should NOT be default
        $addr1 = $this->service->findById($id1);
        $this->assertEquals(0, $addr1->is_default);

        // Set Address 1 as default
        $this->service->setDefault($id1, 1);

        $addr1 = $this->service->findById($id1);
        $this->assertEquals(1, $addr1->is_default);

        $addr2 = $this->service->findById($id2);
        $this->assertEquals(0, $addr2->is_default);
    }

    public function testDelete() {
        $data = [
            'label' => 'Delete Me',
            'name' => 'John Doe',
            'address' => '123 Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'country' => 'UK',
            'is_default' => 0
        ];
        $id = $this->service->save(1, $data);
        $this->assertCount(1, $this->service->getByUserId(1));

        $this->service->delete($id, 1);
        $this->assertCount(0, $this->service->getByUserId(1));
    }
}
