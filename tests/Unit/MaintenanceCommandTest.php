<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Commands\MaintenanceDownCommand;
use App\Commands\MaintenanceUpCommand;
use App\Services\SettingsService;
use App\Repositories\SettingsRepository;
use Tests\NullLogger;

class MaintenanceCommandTest extends TestCase {
    private ?SettingsService $settingsService = null;
    private ?\PDO $db = null;

    public function setUp(): void {
        $this->db = \App\Core\Database::getConnection();
        $repo = new SettingsRepository($this->db);
        $this->settingsService = new SettingsService($repo, new NullLogger());
    }

    public function testDownCommand() {
        $command = new MaintenanceDownCommand($this->settingsService);
        
        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Maintenance mode ENABLED', $output);
        
        $this->assertEquals('1', $this->settingsService->get('maintenance_mode'));
    }

    public function testUpCommand() {
        // First set it to 1
        $this->settingsService->set('maintenance_mode', '1');
        
        $command = new MaintenanceUpCommand($this->settingsService);
        
        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Maintenance mode DISABLED', $output);
        
        $this->assertEquals('0', $this->settingsService->get('maintenance_mode'));
    }
}
