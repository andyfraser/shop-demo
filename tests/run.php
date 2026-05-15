<?php

require_once __DIR__ . '/../src/Core/Autoloader.php';
\App\Core\Autoloader::register();

require_once __DIR__ . '/../src/Helpers.php';

@session_start();

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/NullLogger.php';

use Tests\AssertionFailedException;

$testDir = __DIR__ . '/Unit';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir));
$testFiles = new RegexIterator($files, '/Test\.php$/');

$passed = 0;
$failed = 0;
$assertions = 0;
$failures = [];

// Setup test database
$testDbPath = __DIR__ . '/test.db';
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}

define('DB_CONFIG', [
    'driver' => 'sqlite',
    'path'   => $testDbPath
]);

echo "Running migrations on test database...\n";
$pdo = \App\Core\Database::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// Create migrations table if it doesn't exist
$migrationsTableSql = $driver === 'mysql' 
    ? "CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    : "CREATE TABLE IF NOT EXISTS migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );";
$pdo->exec($migrationsTableSql);

// Get already applied migrations
$stmt = $pdo->query("SELECT migration FROM migrations");
$appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all migration files
$migrationFiles = glob(__DIR__ . '/../migrations/m*_*.php');
sort($migrationFiles);

foreach ($migrationFiles as $file) {
    $migrationName = basename($file, '.php');
    if (!in_array($migrationName, $appliedMigrations)) {
        echo "Applying migration: $migrationName... ";
        $migrationInstance = require $file;
        $sql = $migrationInstance->up($driver);
        
        if (is_string($sql)) {
            if ($driver === 'mysql') {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $s) {
                    if (!empty($s)) {
                        try {
                            $pdo->exec($s);
                        } catch (PDOException $e) {
                            if ($e->getCode() == '42000' && (str_contains($e->getMessage(), '1061') || str_contains($e->getMessage(), 'Duplicate key name'))) {
                                continue;
                            }
                            throw $e;
                        }
                    }
                }
            } else {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'duplicate column name')) {
                        // ignore
                    } else {
                        throw $e;
                    }
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $stmt->execute(['migration' => $migrationName]);
        echo "Done.\n";
    }
}

// Seed the database
echo "Seeding test database... ";
$logger = new \Tests\NullLogger();
$categoryRepo = new \App\Repositories\CategoryRepository($pdo, $logger);
$productRepo = new \App\Repositories\ProductRepository($pdo, $logger);
$deliveryRepo = new \App\Repositories\DeliveryRepository($pdo, $logger);
$userRepo = new \App\Repositories\UserRepository($pdo, $logger);
$attributeRepo = new \App\Repositories\AttributeRepository($pdo, $logger);

$seedService = new \App\Services\DatabaseSeedService(
    $categoryRepo,
    $productRepo,
    $deliveryRepo,
    $userRepo,
    $attributeRepo
);
$seedService->seed();
echo "Done.\n";

echo "\nRunning tests...\n\n";

foreach ($testFiles as $file) {
    $beforeClasses = get_declared_classes();
    require_once $file->getPathname();
    $afterClasses = get_declared_classes();
    $newClasses = array_diff($afterClasses, $beforeClasses);
    
    foreach ($newClasses as $testClass) {
        $reflection = new ReflectionClass($testClass);
        if (!$reflection->isAbstract() && $reflection->isSubclassOf('Tests\TestCase')) {
            $instance = $reflection->newInstance();
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if (strpos($method->name, 'test') === 0) {
                    $instance->clearExpectedException();
                    try {
                        if ($reflection->hasMethod('setUp')) {
                            $instance->setUp();
                        }
                        $instance->{$method->name}();
                        
                        if ($instance->getExpectedException()) {
                            throw new AssertionFailedException("Expected exception " . $instance->getExpectedException() . " was not thrown.");
                        }

                        echo ".";
                        $passed++;
                    } catch (AssertionFailedException $e) {
                        echo "F";
                        $failed++;
                        $failures[] = [
                            'class' => $testClass,
                            'method' => $method->name,
                            'message' => $e->getMessage(),
                            'file' => $file->getPathname(),
                            'line' => $e->getLine()
                        ];
                    } catch (Throwable $e) {
                        if ($instance->getExpectedException() && $e instanceof ($instance->getExpectedException())) {
                            echo ".";
                            $passed++;
                        } else {
                            echo "E";
                            $failed++;
                            $failures[] = [
                                'class' => $testClass,
                                'method' => $method->name,
                                'message' => "Unhandled Exception/Error: " . get_class($e) . ": " . $e->getMessage(),
                                'file' => $file->getPathname(),
                                'line' => $e->getLine()
                            ];
                        }
                    } finally {
                        if ($reflection->hasMethod('tearDown')) {
                            $instance->tearDown();
                        }
                    }
                }
            }
            $assertions += $instance->getAssertionCount();
        }
    }
}

echo "\n\n";

if ($failed > 0) {
    echo "Failures:\n";
    foreach ($failures as $i => $failure) {
        echo ($i + 1) . ") {$failure['class']}::{$failure['method']}\n";
        echo "{$failure['message']}\n";
        echo "{$failure['file']}:{$failure['line']}\n\n";
    }
}

echo "Tests: " . ($passed + $failed) . ", Assertions: $assertions, Failures: $failed\n";

// Cleanup test database
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}

exit($failed > 0 ? 1 : 0);
