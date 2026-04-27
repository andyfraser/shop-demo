<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $config = self::getConfig();
            $driver = $config['driver'] ?? 'sqlite';

            if ($driver === 'sqlite') {
                $dbPath = self::getSqlitePath($config);
                $isNewDatabase = !file_exists($dbPath);

                self::$pdo = new PDO('sqlite:' . $dbPath);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$pdo->exec('PRAGMA foreign_keys = ON');

                if ($isNewDatabase) {
                    self::initDatabase();
                }
            } else if ($driver === 'mysql') {
                $host    = $config['host'] ?? 'localhost';
                $dbname  = $config['dbname'] ?? 'shop_demo';
                $user    = $config['user'] ?? 'root';
                $pass    = $config['pass'] ?? '';
                $charset = $config['charset'] ?? 'utf8mb4';

                try {
                    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
                    self::$pdo = new PDO($dsn, $user, $pass);
                    
                    // Check if database is empty
                    $tables = self::$pdo->query("SHOW TABLES")->fetchAll();
                    if (empty($tables)) {
                        self::initDatabase();
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
            } else {
                throw new \Exception("Unsupported database driver: " . $driver);
            }

            self::migrations();
        }
        return self::$pdo;
    }

    public static function closeConnection(): void {
        self::$pdo = null;
    }

    public static function getConfig(): array {
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
        self::$pdo = new PDO($dsnWithDb, $user, $pass);
        self::initDatabase();
    }

    private static function initDatabase(): void {
        $pdo = self::$pdo;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $schemaFile = ($driver === 'mysql') ? 'mysql_schema.sql' : 'sqlite_schema.sql';
        $schemaPath = __DIR__ . '/../../' . $schemaFile;
        
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            
            if ($driver === 'mysql') {
                // Split by ; and execute one by one for MySQL
                $statements = array_filter(array_map('trim', explode(';', $schema)));
                foreach ($statements as $sql) {
                    if (empty($sql)) continue;
                    try {
                        $pdo->exec($sql);
                    } catch (PDOException $e) {
                        // Ignore "index already exists" errors for MySQL if we were re-running
                        if (str_contains($e->getMessage(), 'Duplicate key name')) continue;
                        throw $e;
                    }
                }
            } else {
                $pdo->exec($schema);
            }
        }

        $hash = password_hash('password', PASSWORD_DEFAULT);
        $ignoreStr = ($driver === 'mysql') ? 'IGNORE' : 'OR IGNORE';
        
        $pdo->prepare(
            "INSERT {$ignoreStr} INTO users (id, name, email, password_hash, role, is_verified)
             VALUES (1, 'Admin', 'admin@shop.local', ?, 'admin', 1)"
        )->execute([$hash]);
        $pdo->prepare(
            "INSERT {$ignoreStr} INTO users (id, name, email, password_hash, role, is_verified)
             VALUES (2, 'Jane Smith', 'jane@example.com', ?, 'customer', 1)"
        )->execute([$hash]);
    }

    private static function migrations(): void {
        $pdo = self::$pdo;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_COLUMN, 1);
        } else {
            $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        if (!in_array('customer_email', $cols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN customer_email TEXT");
        }
        if (!in_array('customer_name', $cols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN customer_name TEXT");
        }
        if (!in_array('total_vat_amount', $cols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN total_vat_amount REAL DEFAULT 0.0");
        }

        // Products table migrations
        if ($driver === 'sqlite') {
            $p_cols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_COLUMN, 1);
        } else {
            $p_cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        if (!in_array('featured', $p_cols)) {
            $default = ($driver === 'mysql') ? '0' : '0';
            $type = ($driver === 'mysql') ? 'TINYINT(1)' : 'INTEGER';
            $pdo->exec("ALTER TABLE products ADD COLUMN featured $type DEFAULT $default");
        }
        if (!in_array('vat_rate', $p_cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN vat_rate REAL DEFAULT 20.0");
        }
        if (!in_array('sku', $p_cols)) {
            $type = ($driver === 'mysql') ? 'VARCHAR(255)' : 'TEXT';
            // SQLite doesn't support adding UNIQUE columns via ALTER TABLE easily
            $pdo->exec("ALTER TABLE products ADD COLUMN sku $type");
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_products_sku ON products(sku)");
        }
        if (!in_array('force_variant', $p_cols)) {
            $type = ($driver === 'mysql') ? 'TINYINT(1)' : 'INTEGER';
            $pdo->exec("ALTER TABLE products ADD COLUMN force_variant $type DEFAULT 0");
        }

        // Order Items table migrations
        if ($driver === 'sqlite') {
            $oi_cols = $pdo->query("PRAGMA table_info(order_items)")->fetchAll(PDO::FETCH_COLUMN, 1);
        } else {
            $oi_cols = $pdo->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        if (!in_array('vat_rate', $oi_cols)) {
            $pdo->exec("ALTER TABLE order_items ADD COLUMN vat_rate REAL DEFAULT 0.0");
        }
        if (!in_array('vat_amount', $oi_cols)) {
            $pdo->exec("ALTER TABLE order_items ADD COLUMN vat_amount REAL DEFAULT 0.0");
        }

        // Users table migrations
        if ($driver === 'sqlite') {
            $u_cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
        } else {
            $u_cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        if (!in_array('is_verified', $u_cols)) {
            $default = ($driver === 'mysql') ? '0' : '0';
            $type = ($driver === 'mysql') ? 'TINYINT(1)' : 'INTEGER';
            $pdo->exec("ALTER TABLE users ADD COLUMN is_verified $type DEFAULT $default");
        }
        if (!in_array('verification_token', $u_cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN verification_token TEXT");
        }

        // Force verify seeded accounts if they were already there
        $pdo->exec("UPDATE users SET is_verified = 1 WHERE id IN (1, 2)");

        // Remember-me tokens table
        if ($driver === 'sqlite') {
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        }
        if (!in_array('remember_tokens', $tables)) {
            if ($driver === 'mysql') {
                $pdo->exec("CREATE TABLE remember_tokens (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    user_id    INT NOT NULL,
                    token      VARCHAR(64) NOT NULL UNIQUE,
                    expires_at INT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )");
            } else {
                $pdo->exec("CREATE TABLE remember_tokens (
                    id         INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    token      TEXT NOT NULL UNIQUE,
                    expires_at INTEGER NOT NULL
                )");
            }
        }

        // Product Variants table
        if (!in_array('product_variants', $tables)) {
            if ($driver === 'mysql') {
                $pdo->exec("CREATE TABLE product_variants (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    sku VARCHAR(255) UNIQUE,
                    price DOUBLE,
                    stock INT DEFAULT 0,
                    active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB");
            } else {
                $pdo->exec("CREATE TABLE product_variants (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    name TEXT NOT NULL,
                    sku TEXT UNIQUE,
                    price REAL,
                    stock INTEGER DEFAULT 0,
                    active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            }
        }

        // Add variant_id to order_items
        if (!in_array('variant_id', $oi_cols)) {
            $pdo->exec("ALTER TABLE order_items ADD COLUMN variant_id " . ($driver === 'mysql' ? 'INT' : 'INTEGER'));
        }
    }
}
