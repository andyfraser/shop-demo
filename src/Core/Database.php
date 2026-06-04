<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;
    private static ?array $runtimeConfig = null;

    public static function setRuntimeConfig(array $config): void {
        self::$runtimeConfig = $config;
        self::closeConnection();
    }

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $config = self::getConfig();
            $driver = $config['driver'] ?? 'sqlite';

            if ($driver === 'sqlite') {
                $dbPath = self::getSqlitePath($config);
                $dsn = 'sqlite:' . $dbPath;
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    self::$pdo = new LoggedPDO($dsn);
                } else {
                    self::$pdo = new PDO($dsn);
                }
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 seconds busy timeout
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            } else if ($driver === 'mysql') {
                $host    = $config['host'] ?? 'localhost';
                $dbname  = $config['dbname'] ?? 'shop_demo';
                $user    = $config['user'] ?? 'root';
                $pass    = $config['pass'] ?? '';
                $charset = $config['charset'] ?? 'utf8mb4';

                try {
                    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        self::$pdo = new LoggedPDO($dsn, $user, $pass);
                    } else {
                        self::$pdo = new PDO($dsn, $user, $pass);
                    }
                } catch (PDOException $e) {
                    // If database doesn't exist (Error code 1049)
                    if ($e->getCode() == 1049) {
                        self::createMySQLDatabase($host, $dbname, $user, $pass, $charset);
                    } else {
                        throw $e;
                    }
                }

                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 seconds connection timeout
                self::$pdo->exec("SET session innodb_lock_wait_timeout=5"); // 5 seconds lock wait timeout
            } else {
                throw new \Exception("Unsupported database driver: " . $driver);
            }
        }
        return self::$pdo;
    }

    public static function closeConnection(): void {
        self::$pdo = null;
    }

    public static function getConfig(): array {
        if (self::$runtimeConfig !== null) {
            return self::$runtimeConfig;
        }
        return defined('DB_CONFIG') ? DB_CONFIG : [
            'driver' => 'sqlite',
            'path'   => __DIR__ . '/../../shop.db',
        ];
    }

    public static function getSqlitePath(?array $config = null): string {
        $config = $config ?? self::getConfig();
        return $config['path'] ?? __DIR__ . '/../../shop.db';
    }

    private static function createMySQLDatabase($host, $dbname, $user, $pass, $charset): void {
        $dsn = "mysql:host={$host};charset={$charset}";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET {$charset}");
        
        $dsnWithDb = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            self::$pdo = new LoggedPDO($dsnWithDb, $user, $pass);
        } else {
            self::$pdo = new PDO($dsnWithDb, $user, $pass);
        }
    }
}
