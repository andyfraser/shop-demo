<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    user_id INT NOT NULL,
                    rating INT NOT NULL,
                    comment TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS reviews (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    rating INTEGER NOT NULL,
                    comment TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ";
        }
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS reviews;";
    }
};
