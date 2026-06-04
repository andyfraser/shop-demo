<?php

use App\Commands\CacheClearCommand;
use App\Commands\MigrateCommand;
use App\Commands\MigrateRollbackCommand;
use App\Commands\RotateLogsCommand;
use App\Commands\ImageCleanupCommand;
use App\Commands\SeedCommand;
use App\Commands\RecoverCartsCommand;
use App\Commands\MaintenanceDownCommand;
use App\Commands\MaintenanceUpCommand;
use App\Commands\SchedulePauseCommand;
use App\Commands\ScheduleResumeCommand;
use App\Core\Cache\CacheInterface;
use App\Services\ImageCleanupServiceInterface;
use App\Services\MigrationServiceInterface;
use App\Services\DatabaseSeedServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\SettingsServiceInterface;
use Psr\Log\LoggerInterface;

return function($c, array $config) {
    return [
        CacheClearCommand::class => function($c) {
            return new CacheClearCommand($c->get(CacheInterface::class));
        },
        RotateLogsCommand::class => function($c) use ($config) {
            $logPath = $config['app']['log_path'] ?? __DIR__ . '/../../logs/app.log';
            $logDir = dirname($logPath);
            $retention = $config['app']['log_retention_days'] ?? 30;
            return new RotateLogsCommand($logDir, $retention, $c->get(LoggerInterface::class));
        },
        ImageCleanupCommand::class => function($c) {
            return new ImageCleanupCommand($c->get(ImageCleanupServiceInterface::class));
        },
        MigrateCommand::class => function($c) {
            return new MigrateCommand(
                $c->get(MigrationServiceInterface::class), 
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class)
            );
        },
        MigrateRollbackCommand::class => function($c) {
            return new MigrateRollbackCommand(
                $c->get(MigrationServiceInterface::class), 
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class)
            );
        },
        SeedCommand::class => function($c) {
            return new SeedCommand($c->get(DatabaseSeedServiceInterface::class), $c->get(LoggerInterface::class));
        },
        RecoverCartsCommand::class => function($c) {
            return new RecoverCartsCommand($c->get(PDO::class), $c->get(\App\Core\Events\EventDispatcherInterface::class), $c->get(LoggerInterface::class));
        },
        MaintenanceDownCommand::class => function($c) {
            return new MaintenanceDownCommand($c->get(SettingsServiceInterface::class));
        },
        MaintenanceUpCommand::class => function($c) {
            return new MaintenanceUpCommand($c->get(SettingsServiceInterface::class));
        },
        SchedulePauseCommand::class => function($c) {
            return new SchedulePauseCommand($c->get(SettingsServiceInterface::class));
        },
        ScheduleResumeCommand::class => function($c) {
            return new ScheduleResumeCommand($c->get(SettingsServiceInterface::class));
        },
    ];
};
