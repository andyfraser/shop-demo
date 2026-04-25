<?php

namespace Tests\Unit;

use Tests\TestCase;

class ServicesTest extends TestCase {
    public function testServicesFileExists() {
        $this->assertTrue(file_exists(__DIR__ . '/../../config/services.php'));
    }

    public function testServicesFileReturnsFunction() {
        $servicesFactory = require __DIR__ . '/../../config/services.php';
        $this->assertTrue(is_callable($servicesFactory));
    }

    public function testAllServicesHaveValidFactories() {
        $config = [
            'app' => ['debug' => false, 'log_retention_days' => 30]
        ];
        $servicesFactory = require __DIR__ . '/../../config/services.php';
        $services = $servicesFactory($config);
        
        $this->assertTrue(is_array($services));
        foreach ($services as $id => $factory) {
            $this->assertTrue(is_callable($factory), "Service factory for $id must be callable");
        }
    }
}
