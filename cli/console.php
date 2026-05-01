<?php

require_once __DIR__ . '/../src/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../src/Helpers.php';

use App\Core\Container;
use App\Core\Scheduler;
use App\Commands\CommandInterface;

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/../config/config.php')) {
    $config = require __DIR__ . '/../config/config.php';
}

if (!defined('DB_CONFIG')) {
    define('DB_CONFIG', $config['db'] ?? [
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/../shop.db',
    ]);
}

$container = new Container();

// Register services from config/services.php
$servicesFactory = require __DIR__ . '/../config/services.php';
$services = $servicesFactory($config);
foreach ($services as $id => $factory) {
    $container->set($id, $factory);
}

if (!defined('BASE_URL')) {
    $settings = $container->get(\App\Services\SettingsService::class);
    $baseUrlSetting = $settings->get('base_url');
    define('BASE_URL', $baseUrlSetting !== null ? (string)$baseUrlSetting : ($config['site']['base_url'] ?? ''));
}

// Auto-discover commands
$commands = [];
$commandDir = __DIR__ . '/../src/Commands';
$files = scandir($commandDir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..' || $file === 'CommandInterface.php') {
        continue;
    }

    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
        if (class_exists($className)) {
            $reflection = new ReflectionClass($className);
            if ($reflection->implementsInterface(CommandInterface::class) && !$reflection->isAbstract()) {
                /** @var CommandInterface $command */
                $command = $container->get($className);
                $commands[$command->getName()] = $command;
            }
        }
    }
}

$db = $container->get(PDO::class);
$scheduler = new Scheduler($db, array_values($commands));

// CLI Router
$action = $argv[1] ?? 'list';

if ($action === 'schedule:run') {
    $scheduler->run();
} elseif (isset($commands[$action])) {
    echo "Running command: {$action}...\n";
    $exitCode = $commands[$action]->execute();
    echo "Finished with exit code: {$exitCode}\n";
} else {
    echo "Usage: php cli/console.php [command]\n\n";
    echo "Available commands:\n";
    echo "  schedule:run          Run all scheduled tasks\n";
    foreach ($commands as $name => $cmd) {
        $scheduleInfo = $cmd->getSchedule() ? " (Scheduled: {$cmd->getSchedule()})" : "";
        echo "  " . str_pad($name, 20) . " " . $cmd->getDescription() . $scheduleInfo . "\n";
    }
}
