<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS wishlist_settings (
                    user_id INT PRIMARY KEY,
                    is_public TINYINT(1) DEFAULT 0,
                    share_hash VARCHAR(64) UNIQUE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS wishlist_settings (
                    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
                    is_public INTEGER DEFAULT 0,
                    share_hash TEXT UNIQUE
                );
            ";
        }
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS wishlist_settings;";
    }
};
