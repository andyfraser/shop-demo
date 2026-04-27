<?php

require_once __DIR__ . '/src/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Database;

// Load configuration
if (file_exists(__DIR__ . '/config/config.php')) {
    $config = require __DIR__ . '/config/config.php';
    if (isset($config['db'])) {
        define('DB_CONFIG', $config['db']);
    }
}

if (!defined('DB_CONFIG')) {
    define('DB_CONFIG', [
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/shop.db',
    ]);
}

$pdo = Database::getConnection();
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

$mode = $argv[1] ?? 'up';

if ($mode === '--rollback') {
    rollback($pdo);
} else {
    migrate($pdo);
}

function migrate($pdo) {
    // Get already applied migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get all migration files
    $migrationFiles = glob(__DIR__ . '/migrations/m*_*.php');
    sort($migrationFiles);

    $newMigrations = [];
    foreach ($migrationFiles as $file) {
        $migrationName = basename($file, '.php');
        if (!in_array($migrationName, $appliedMigrations)) {
            $newMigrations[] = ['file' => $file, 'name' => $migrationName];
        }
    }

    if (empty($newMigrations)) {
        echo "No new migrations to apply.\n";
        return;
    }

    foreach ($newMigrations as $migration) {
        echo "Applying migration: {$migration['name']}... ";
        
        $migrationInstance = require $migration['file'];
        
        try {
            $sql = $migrationInstance->up($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
            
            // Execute statements one by one if it's a string with multiple statements
            if (is_string($sql)) {
                if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $s) {
                        if (!empty($s)) {
                            try {
                                $pdo->exec($s);
                            } catch (PDOException $e) {
                                // Ignore duplicate key/index errors for MySQL
                                if ($e->getCode() == '42000' && (str_contains($e->getMessage(), '1061') || str_contains($e->getMessage(), 'Duplicate key name'))) {
                                    continue;
                                }
                                throw $e;
                            }
                        }
                    }
                } else {
                    $pdo->exec($sql);
                }
            } else if (is_array($sql)) {
                foreach ($sql as $s) {
                    try {
                        $pdo->exec($s);
                    } catch (PDOException $e) {
                        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' && $e->getCode() == '42000' && (str_contains($e->getMessage(), '1061') || str_contains($e->getMessage(), 'Duplicate key name'))) {
                            continue;
                        }
                        throw $e;
                    }
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
            $stmt->execute(['migration' => $migration['name']]);
            
            echo "Done.\n";
        } catch (Exception $e) {
            echo "Failed!\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\nAll migrations applied successfully.\n";
}

function rollback($pdo) {
    // Get the last applied migration
    $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
    $lastMigration = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lastMigration) {
        echo "No migrations found to rollback.\n";
        return;
    }

    $migrationName = $lastMigration['migration'];
    $file = __DIR__ . '/migrations/' . $migrationName . '.php';

    if (!file_exists($file)) {
        echo "Error: Migration file '{$file}' not found. Cannot rollback.\n";
        exit(1);
    }

    echo "Rolling back migration: {$migrationName}... ";

    $migrationInstance = require $file;

    try {
        $sql = $migrationInstance->down($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        
        if (is_string($sql)) {
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $s) {
                    if (!empty($s)) $pdo->exec($s);
                }
            } else {
                $pdo->exec($sql);
            }
        } else if (is_array($sql)) {
            foreach ($sql as $s) {
                $pdo->exec($s);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = :migration");
        $stmt->execute(['migration' => $migrationName]);

        echo "Done.\n";
    } catch (Exception $e) {
        echo "Failed!\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
