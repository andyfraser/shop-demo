<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE promotions ADD COLUMN usage_limit_per_user INT;
                ALTER TABLE promotions ADD COLUMN priority INT DEFAULT 0;
                ALTER TABLE promotions ADD COLUMN stackable TINYINT(1) DEFAULT 0;
                ALTER TABLE promotions ADD COLUMN target_role VARCHAR(50);

                ALTER TABLE orders ADD COLUMN applied_promo_name VARCHAR(255);
                ALTER TABLE orders ADD COLUMN applied_promo_code VARCHAR(50);

                CREATE TABLE promotion_targets_new (
                    promotion_id INT NOT NULL,
                    target_id INT NOT NULL,
                    is_exclusion TINYINT(1) DEFAULT 0,
                    PRIMARY KEY (promotion_id, target_id, is_exclusion),
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                INSERT INTO promotion_targets_new (promotion_id, target_id) SELECT promotion_id, target_id FROM promotion_targets;
                DROP TABLE promotion_targets;
                RENAME TABLE promotion_targets_new TO promotion_targets;

                CREATE TABLE IF NOT EXISTS promotion_tiers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    promotion_id INT NOT NULL,
                    min_amount DOUBLE NOT NULL,
                    value DOUBLE NOT NULL,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS promotion_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    promotion_id INT NOT NULL,
                    code VARCHAR(50) NOT NULL UNIQUE,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS order_promotions (
                    order_id INT NOT NULL,
                    promotion_id INT NOT NULL,
                    discount_amount DOUBLE NOT NULL,
                    promo_code VARCHAR(50),
                    PRIMARY KEY (order_id, promotion_id),
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS cart_promotions (
                    cart_id INT NOT NULL,
                    promo_code VARCHAR(50) NOT NULL,
                    PRIMARY KEY (cart_id, promo_code),
                    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                ALTER TABLE promotions ADD COLUMN usage_limit_per_user INTEGER;
                ALTER TABLE promotions ADD COLUMN priority INTEGER DEFAULT 0;
                ALTER TABLE promotions ADD COLUMN stackable INTEGER DEFAULT 0;
                ALTER TABLE promotions ADD COLUMN target_role TEXT;

                ALTER TABLE orders ADD COLUMN applied_promo_name TEXT;
                ALTER TABLE orders ADD COLUMN applied_promo_code TEXT;

                CREATE TABLE promotion_targets_new (
                    promotion_id INTEGER NOT NULL,
                    target_id INTEGER NOT NULL,
                    is_exclusion INTEGER DEFAULT 0,
                    PRIMARY KEY (promotion_id, target_id, is_exclusion),
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                );
                INSERT INTO promotion_targets_new (promotion_id, target_id) SELECT promotion_id, target_id FROM promotion_targets;
                DROP TABLE promotion_targets;
                ALTER TABLE promotion_targets_new RENAME TO promotion_targets;

                CREATE TABLE IF NOT EXISTS promotion_tiers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    promotion_id INTEGER NOT NULL,
                    min_amount REAL NOT NULL,
                    value REAL NOT NULL,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS promotion_codes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    promotion_id INTEGER NOT NULL,
                    code TEXT NOT NULL UNIQUE,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS order_promotions (
                    order_id INTEGER NOT NULL,
                    promotion_id INTEGER NOT NULL,
                    discount_amount REAL NOT NULL,
                    promo_code TEXT,
                    PRIMARY KEY (order_id, promotion_id),
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS cart_promotions (
                    cart_id INTEGER NOT NULL,
                    promo_code TEXT NOT NULL,
                    PRIMARY KEY (cart_id, promo_code),
                    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE
                );
            ";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP TABLE IF EXISTS cart_promotions;
                DROP TABLE IF EXISTS order_promotions;
                DROP TABLE IF EXISTS promotion_codes;
                DROP TABLE IF EXISTS promotion_tiers;
                ALTER TABLE promotion_targets DROP COLUMN is_exclusion;
                ALTER TABLE orders DROP COLUMN applied_promo_code;
                ALTER TABLE orders DROP COLUMN applied_promo_name;
                ALTER TABLE promotions DROP COLUMN target_role;
                ALTER TABLE promotions DROP COLUMN stackable;
                ALTER TABLE promotions DROP COLUMN priority;
                ALTER TABLE promotions DROP COLUMN usage_limit_per_user;
            ";
        } else {
            return "
                DROP TABLE IF EXISTS cart_promotions;
                DROP TABLE IF EXISTS order_promotions;
                DROP TABLE IF EXISTS promotion_codes;
                DROP TABLE IF EXISTS promotion_tiers;
            ";
        }
    }
};
