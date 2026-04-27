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
     * We simulate this by creating an instance without constructor and setting a missing property.
     */
    public function testPdoHydrationBeforeConstructor() {
        $reflection = new \ReflectionClass(TestModel::class);
        $model = $reflection->newInstanceWithoutConstructor();
        
        // This should NOT throw "Typed property App\Models\Model::$logger must not be accessed before initialization"
        $model->unmapped_before_const = 'val';
        
        $this->assertEquals('val', $model->unmapped_before_const);
        
        // Now call constructor. It should flush the stashed log.
        $logger = new MockLogger();
        $model->__construct($logger);
        
        $this->assertCount(1, $logger->logs, 'Stashed log should be flushed during constructor');
        $this->assertStringContainsString('unmapped_before_const', $logger->logs[0]['message']);

        // Setting another one after constructor should log immediately
        $model->unmapped_after_const = 'val2';
        $this->assertEquals('val2', $model->unmapped_after_const);
        $this->assertCount(2, $logger->logs, 'Second log should be added');
        $this->assertStringContainsString('unmapped_after_const', $logger->logs[1]['message']);
    }

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
