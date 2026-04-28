<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS order_status_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    notes TEXT,
                    created_by_user_id INT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS order_status_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                    status TEXT NOT NULL,
                    notes TEXT,
                    created_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ";
        }
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS order_status_history;";
    }
};
