<?php

use App\Commands\RotateLogsCommand;
use App\Commands\ImageCleanupCommand;
use App\Commands\SeedCommand;
use App\Services\ImageCleanupServiceInterface;
use App\Services\MigrationServiceInterface;
use App\Services\DatabaseSeedServiceInterface;

return function($c, array $config) {
    return [
        RotateLogsCommand::class => function($c) use ($config) {
            $logPath = $config['app']['log_path'] ?? __DIR__ . '/../../logs/app.log';
            $logDir = dirname($logPath);
            $retention = $config['app']['log_retention_days'] ?? 30;
            return new RotateLogsCommand($logDir, $retention);
        },
        ImageCleanupCommand::class => function($c) {
            return new ImageCleanupCommand($c->get(ImageCleanupServiceInterface::class));
        },
        App\Commands\MigrateCommand::class => function($c) {
            return new App\Commands\MigrateCommand($c->get(MigrationServiceInterface::class));
        },
        App\Commands\MigrateRollbackCommand::class => function($c) {
            return new App\Commands\MigrateRollbackCommand($c->get(MigrationServiceInterface::class));
        },
        SeedCommand::class => function($c) {
            return new SeedCommand($c->get(DatabaseSeedServiceInterface::class));
        },
    ];
};
