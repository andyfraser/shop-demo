<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS user_addresses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    address TEXT NOT NULL,
                    city VARCHAR(100) NOT NULL,
                    postcode VARCHAR(20) NOT NULL,
                    country VARCHAR(100) NOT NULL,
                    is_default TINYINT(1) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS user_addresses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    name TEXT NOT NULL,
                    address TEXT NOT NULL,
                    city TEXT NOT NULL,
                    postcode TEXT NOT NULL,
                    country TEXT NOT NULL,
                    is_default INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ";
        }
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS user_addresses;";
    }
};
