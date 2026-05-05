<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS attributes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS attribute_values (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    attribute_id INT NOT NULL,
                    value VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS product_attribute_values (
                    product_id INT NOT NULL,
                    attribute_value_id INT NOT NULL,
                    PRIMARY KEY (product_id, attribute_value_id),
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE INDEX idx_attr_val_attr ON attribute_values(attribute_id);
                CREATE INDEX idx_prod_attr_val_val ON product_attribute_values(attribute_value_id);
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS attributes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS attribute_values (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    attribute_id INTEGER NOT NULL REFERENCES attributes(id) ON DELETE CASCADE,
                    value TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS product_attribute_values (
                    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    attribute_value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE CASCADE,
                    PRIMARY KEY (product_id, attribute_value_id)
                );

                CREATE INDEX IF NOT EXISTS idx_attr_val_attr ON attribute_values(attribute_id);
                CREATE INDEX IF NOT EXISTS idx_prod_attr_val_val ON product_attribute_values(attribute_value_id);
            ";
        }
    }

    public function down(string $driver): string {
        return "
            DROP TABLE IF EXISTS product_attribute_values;
            DROP TABLE IF EXISTS attribute_values;
            DROP TABLE IF EXISTS attributes;
        ";
    }
};
