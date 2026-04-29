<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS carts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    session_id VARCHAR(100) NOT NULL,
                    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
                    recovery_email_sent_at DATETIME DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX (session_id),
                    INDEX (user_id)
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS cart_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cart_id INT NOT NULL,
                    product_id INT NOT NULL,
                    variant_id INT NULL,
                    qty INT NOT NULL DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS carts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NULL REFERENCES users(id) ON DELETE CASCADE,
                    session_id TEXT NOT NULL,
                    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
                    recovery_email_sent_at DATETIME DEFAULT NULL
                );
                CREATE INDEX idx_carts_session ON carts(session_id);
                CREATE INDEX idx_carts_user ON carts(user_id);

                CREATE TABLE IF NOT EXISTS cart_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    cart_id INTEGER NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
                    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    variant_id INTEGER NULL REFERENCES product_variants(id) ON DELETE CASCADE,
                    qty INTEGER NOT NULL DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ";
        }
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS cart_items; DROP TABLE IF EXISTS carts;";
    }
};
