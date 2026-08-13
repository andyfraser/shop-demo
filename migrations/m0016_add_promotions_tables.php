<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS promotions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    code VARCHAR(50) UNIQUE,
                    type VARCHAR(20) NOT NULL,
                    value DOUBLE NOT NULL,
                    target_type VARCHAR(20) NOT NULL,
                    min_order_amount DOUBLE DEFAULT 0,
                    start_date DATETIME,
                    end_date DATETIME,
                    usage_limit INT,
                    used_count INT DEFAULT 0,
                    active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS promotion_targets (
                    promotion_id INT NOT NULL,
                    target_id INT NOT NULL,
                    PRIMARY KEY (promotion_id, target_id),
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                ALTER TABLE orders ADD COLUMN promotion_id INT, ADD CONSTRAINT fk_orders_promotion_id FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL;
                ALTER TABLE orders ADD COLUMN discount_amount DOUBLE DEFAULT 0.0;
                ALTER TABLE carts ADD COLUMN applied_promo_code VARCHAR(50);
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS promotions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    code TEXT UNIQUE,
                    type TEXT NOT NULL,
                    value REAL NOT NULL,
                    target_type TEXT NOT NULL,
                    min_order_amount REAL DEFAULT 0,
                    start_date DATETIME,
                    end_date DATETIME,
                    usage_limit INTEGER,
                    used_count INTEGER DEFAULT 0,
                    active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS promotion_targets (
                    promotion_id INTEGER NOT NULL,
                    target_id INTEGER NOT NULL,
                    PRIMARY KEY (promotion_id, target_id),
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                );

                ALTER TABLE orders ADD COLUMN promotion_id INTEGER REFERENCES promotions(id) ON DELETE SET NULL;
                ALTER TABLE orders ADD COLUMN discount_amount REAL DEFAULT 0.0;
                ALTER TABLE carts ADD COLUMN applied_promo_code TEXT;
            ";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE carts DROP COLUMN applied_promo_code;
                ALTER TABLE orders DROP COLUMN discount_amount;
                ALTER TABLE orders DROP COLUMN promotion_id;
                DROP TABLE IF EXISTS promotion_targets;
                DROP TABLE IF EXISTS promotions;
            ";
        } else {
            // SQLite doesn't support DROP COLUMN easily before 3.35.0, 
            // and this app doesn't seem to use it in other migrations either.
            // We'll just drop the new tables.
            return "
                DROP TABLE IF EXISTS promotion_targets;
                DROP TABLE IF EXISTS promotions;
            ";
        }
    }
};
