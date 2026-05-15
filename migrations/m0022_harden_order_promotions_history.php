<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                -- Drop existing foreign keys
                ALTER TABLE order_promotions DROP FOREIGN KEY order_promotions_ibfk_1;
                ALTER TABLE order_promotions DROP FOREIGN KEY fk_order_promotions_promotion_id;
                
                -- Drop primary key and add auto-increment ID
                ALTER TABLE order_promotions DROP PRIMARY KEY;
                ALTER TABLE order_promotions ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST;
                
                -- Make promotion_id nullable
                ALTER TABLE order_promotions MODIFY promotion_id INT NULL;
                
                -- Restore order_id foreign key (CASCADE)
                ALTER TABLE order_promotions ADD CONSTRAINT fk_order_promotions_order_id 
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;
                
                -- Add new promotion_id foreign key with SET NULL
                ALTER TABLE order_promotions ADD CONSTRAINT fk_order_promotions_promotion_id 
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL;
            ";
        } else {
            // SQLite requires table recreation to change constraints and primary keys
            return "
                CREATE TABLE order_promotions_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER NOT NULL,
                    promotion_id INTEGER NULL,
                    promotion_name TEXT,
                    discount_amount REAL NOT NULL,
                    promo_code TEXT,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL
                );
                
                INSERT INTO order_promotions_new (order_id, promotion_id, promotion_name, discount_amount, promo_code)
                SELECT order_id, promotion_id, promotion_name, discount_amount, promo_code FROM order_promotions;
                
                DROP TABLE order_promotions;
                ALTER TABLE order_promotions_new RENAME TO order_promotions;
            ";
        }
    }

    public function down(string $driver): string {
        // Down migration for this is complex due to data integrity, 
        // but for a demo app we'll provide the basic reversal.
        if ($driver === 'mysql') {
            return "
                ALTER TABLE order_promotions DROP FOREIGN KEY fk_order_promotions_promotion_id;
                ALTER TABLE order_promotions MODIFY promotion_id INT NOT NULL;
                ALTER TABLE order_promotions MODIFY id INT NOT NULL;
                ALTER TABLE order_promotions DROP PRIMARY KEY;
                ALTER TABLE order_promotions DROP COLUMN id;
                ALTER TABLE order_promotions ADD PRIMARY KEY (order_id, promotion_id);
                ALTER TABLE order_promotions ADD CONSTRAINT order_promotions_ibfk_2 
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE;
            ";
        } else {
            return "
                CREATE TABLE order_promotions_old (
                    order_id INTEGER NOT NULL,
                    promotion_id INTEGER NOT NULL,
                    promotion_name TEXT,
                    discount_amount REAL NOT NULL,
                    promo_code TEXT,
                    PRIMARY KEY (order_id, promotion_id),
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE
                );
                
                INSERT INTO order_promotions_old (order_id, promotion_id, promotion_name, discount_amount, promo_code)
                SELECT order_id, promotion_id, promotion_name, discount_amount, promo_code FROM order_promotions WHERE promotion_id IS NOT NULL;
                
                DROP TABLE order_promotions;
                ALTER TABLE order_promotions_old RENAME TO order_promotions;
            ";
        }
    }
};
