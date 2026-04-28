<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AttributeService;
use App\Core\Database;

class AttributeServiceTest extends TestCase {
    private AttributeService $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->service = new AttributeService($this->db, new \Tests\NullLogger());
    }

    public function testGetValuesReturnsValueKey() {
        // Attribute 1 is 'Brand' in seed data
        $values = $this->service->getValues(1);
        
        $this->assertNotEmpty($values);
        foreach ($values as $v) {
            $this->assertTrue(isset($v['id']), "Value should have 'id' key");
            $this->assertTrue(isset($v['value']), "Value should have 'value' key");
            $this->assertFalse(isset($v['name']), "Attribute values should NOT have 'name' key, but 'value' key instead");
        }
    }

    public function testSaveAndGetValues() {
        $attrId = $this->service->save(['name' => 'Test Attribute']);
        
        $valId = $this->service->saveValue([
            'attribute_id' => $attrId,
            'value' => 'Test Value'
        ]);
        
        $values = $this->service->getValues($attrId);
        $this->assertCount(1, $values);
        $this->assertEquals('Test Value', $values[0]['value']);
        $this->assertEquals($valId, $values[0]['id']);
    }
}
