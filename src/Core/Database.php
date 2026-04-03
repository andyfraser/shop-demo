<?php
namespace App\Core;

use PDO;

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . __DIR__ . '/../../shop.db');
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::initDatabase();
        }
        return self::$pdo;
    }

    private static function initDatabase(): void {
        static $done = false;
        if ($done) return;
        $done = true;

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
}
