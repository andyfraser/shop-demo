<?php

return new class {
    public function up(string $driver): string {
        $pk = ($driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = ($driver === 'mysql') ? ' ENGINE=InnoDB' : '';

        return "
            ALTER TABLE products ADD COLUMN is_bundle TINYINT(1) NOT NULL DEFAULT 0;

            CREATE TABLE IF NOT EXISTS product_bundle_items (
                id $pk,
                bundle_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                qty INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bundle_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )$engine;

            CREATE INDEX idx_bundle_items_bundle_id ON product_bundle_items(bundle_id);
            CREATE INDEX idx_bundle_items_product_id ON product_bundle_items(product_id);
        ";
    }

    public function down(string $driver): string {
        return "
            DROP TABLE IF EXISTS product_bundle_items;
            -- SQLite doesn't support dropping columns easily, but for development it's fine
            -- ALTER TABLE products DROP COLUMN is_bundle;
        ";
    }
};
