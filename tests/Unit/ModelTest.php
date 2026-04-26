<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Model;
use Psr\Log\AbstractLogger;

/**
 * A concrete model for testing the abstract Model class.
 */
class TestModel extends Model {
    public string $existing_property = 'default';
}

/**
 * A logger that stores messages for inspection.
 */
class MockLogger extends AbstractLogger {
    public array $logs = [];

    public function log($level, $message, array $context = []): void {
        $this->logs[] = [
            'level'   => $level,
            'message' => $message,
            'context' => $context
        ];
    }
}

class ModelTest extends TestCase {
    public function testFillExistingProperty() {
        $logger = new MockLogger();
        $model = new TestModel($logger);
        
        $model->fill(['existing_property' => 'new value']);
        
        $this->assertEquals('new value', $model->existing_property);
        $this->assertCount(0, $logger->logs, 'Should not log for existing properties');
    }

    public function testFillMissingPropertyTriggersWarning() {
        $logger = new MockLogger();
        $model = new TestModel($logger);
        
        $model->fill(['missing_property' => 'dynamic value']);
        
        // Check property was set
        $this->assertEquals('dynamic value', $model->missing_property);
        
        // Check warning was logged
        $this->assertCount(1, $logger->logs);
        $this->assertEquals('warning', $logger->logs[0]['level']);
        $this->assertStringContainsString("Missing property 'missing_property' in model 'Tests\Unit\TestModel'", $logger->logs[0]['message']);
    }

    public function testDirectDynamicSetTriggersWarning() {
        $logger = new MockLogger();
        $model = new TestModel($logger);
        
        $model->some_random_key = 'some value';
        
        $this->assertEquals('some value', $model->some_random_key);
        $this->assertTrue(isset($model->some_random_key));
        $this->assertCount(1, $logger->logs);
        $this->assertStringContainsString("some_random_key", $logger->logs[0]['message']);
    }

    /**
     * PDO::FETCH_CLASS injects properties BEFORE the constructor is called.
     * We can simulate this by setting properties on an uninitialized object 
     * or by manually calling __set.
     */
    public function testPdoHydrationSimulation() {
        $logger = new MockLogger();
        
        // In a real PDO scenario, PDO creates the object and sets properties.
        // If the property is missing, it triggers __set().
        $model = new TestModel($logger);
        $model->__set('unmapped_db_column', 'secret_value');
        
        $this->assertEquals('secret_value', $model->unmapped_db_column);
        $this->assertCount(1, $logger->logs);
        $this->assertStringContainsString('unmapped_db_column', $logger->logs[0]['message']);
    }
}
