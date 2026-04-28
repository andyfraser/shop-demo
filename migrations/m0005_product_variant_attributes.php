<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS product_variant_attributes (
                    product_id INT NOT NULL,
                    attribute_id INT NOT NULL,
                    PRIMARY KEY (product_id, attribute_id),
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS product_variant_attribute_values (
                    variant_id INT NOT NULL,
                    attribute_value_id INT NOT NULL,
                    PRIMARY KEY (variant_id, attribute_value_id),
                    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
                    FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS product_variant_attributes (
                    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    attribute_id INTEGER NOT NULL REFERENCES attributes(id) ON DELETE CASCADE,
                    PRIMARY KEY (product_id, attribute_id)
                );

                CREATE TABLE IF NOT EXISTS product_variant_attribute_values (
                    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
                    attribute_value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE CASCADE,
                    PRIMARY KEY (variant_id, attribute_value_id)
                );
            ";
        }
    }

    public function down(string $driver): string {
        return "
            DROP TABLE IF EXISTS product_variant_attribute_values;
            DROP TABLE IF EXISTS product_variant_attributes;
        ";
    }
};
