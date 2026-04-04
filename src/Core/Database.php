<?php
namespace App\Core;

use PDO;

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../../shop.db';
            $isNewDatabase = !file_exists($dbPath);

            self::$pdo = new PDO('sqlite:' . $dbPath);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->exec('PRAGMA foreign_keys = ON');

            // Only initialise schema if DB didn't exist before
            if ($isNewDatabase) {
                self::initDatabase();
            }

            self::migrations();
        }
        return self::$pdo;
    }

    private static function initDatabase(): void {
        $pdo = self::$pdo;
        $schemaPath = __DIR__ . '/../../schema.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            $pdo->exec($schema);
        }

        $hash = password_hash('password', PASSWORD_DEFAULT);
        $pdo->prepare(
            "INSERT OR IGNORE INTO users (id, name, email, password_hash, role)
             VALUES (1, 'Admin', 'admin@shop.local', ?, 'admin')"
        )->execute([$hash]);
        $pdo->prepare(
            "INSERT OR IGNORE INTO users (id, name, email, password_hash, role)
             VALUES (2, 'Jane Smith', 'jane@example.com', ?, 'customer')"
        )->execute([$hash]);
    }

    private static function migrations(): void {
        $pdo = self::$pdo;
        
        // Add customer_email and customer_name columns if they don't exist
        $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('customer_email', $cols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN customer_email TEXT");
        }
        if (!in_array('customer_name', $cols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN customer_name TEXT");
        }
    }
}
