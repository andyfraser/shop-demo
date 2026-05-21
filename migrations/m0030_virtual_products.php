<?php

return new class {
    public function up(string $driver): string {
        $pk = ($driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = ($driver === 'mysql') ? ' ENGINE=InnoDB' : '';
        $textType = 'TEXT';

        return "
            ALTER TABLE products ADD COLUMN is_virtual TINYINT(1) NOT NULL DEFAULT 0;
            ALTER TABLE products ADD COLUMN virtual_type VARCHAR(50) DEFAULT NULL;
            ALTER TABLE products ADD COLUMN file_path VARCHAR(255) DEFAULT NULL;
            ALTER TABLE products ADD COLUMN granted_role VARCHAR(50) DEFAULT NULL;

            ALTER TABLE product_variants ADD COLUMN file_path VARCHAR(255) DEFAULT NULL;
            ALTER TABLE product_variants ADD COLUMN granted_role VARCHAR(50) DEFAULT NULL;

            ALTER TABLE cart_items ADD COLUMN metadata $textType DEFAULT NULL;
            ALTER TABLE order_items ADD COLUMN metadata $textType DEFAULT NULL;

            ALTER TABLE delivery_options ADD COLUMN target_role VARCHAR(50) DEFAULT NULL;

            CREATE TABLE IF NOT EXISTS customer_downloads (
                id $pk,
                user_id INTEGER NULL,
                order_item_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                variant_id INTEGER NULL,
                download_token VARCHAR(100) NOT NULL UNIQUE,
                download_count INTEGER NOT NULL DEFAULT 0,
                max_downloads INTEGER NULL,
                expires_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
            )$engine;

            CREATE TABLE IF NOT EXISTS gift_cards (
                id $pk,
                code VARCHAR(50) NOT NULL UNIQUE,
                initial_amount DOUBLE NOT NULL,
                remaining_amount DOUBLE NOT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                sender_name VARCHAR(255) NULL,
                message $textType NULL,
                order_item_id INTEGER NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL
            )$engine;

            CREATE TABLE IF NOT EXISTS product_license_keys (
                id $pk,
                product_id INTEGER NOT NULL,
                license_key VARCHAR(255) NOT NULL UNIQUE,
                is_assigned TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )$engine;

            CREATE TABLE IF NOT EXISTS order_item_licenses (
                id $pk,
                order_item_id INTEGER NOT NULL,
                license_key VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
            )$engine;

            CREATE TABLE IF NOT EXISTS customer_tickets (
                id $pk,
                user_id INTEGER NULL,
                order_item_id INTEGER NOT NULL,
                ticket_code VARCHAR(100) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
            )$engine;

            CREATE INDEX idx_downloads_token ON customer_downloads(download_token);
            CREATE INDEX idx_downloads_user ON customer_downloads(user_id);
            CREATE INDEX idx_gift_cards_code ON gift_cards(code);
            CREATE INDEX idx_license_keys_product ON product_license_keys(product_id);
            CREATE INDEX idx_order_licenses_item ON order_item_licenses(order_item_id);
            CREATE INDEX idx_customer_tickets_code ON customer_tickets(ticket_code);
            CREATE INDEX idx_customer_tickets_user ON customer_tickets(user_id);
        ";
    }

    public function down(string $driver): string {
        return "
            DROP TABLE IF EXISTS customer_tickets;
            DROP TABLE IF EXISTS order_item_licenses;
            DROP TABLE IF EXISTS product_license_keys;
            DROP TABLE IF EXISTS gift_cards;
            DROP TABLE IF EXISTS customer_downloads;
        ";
    }
};
