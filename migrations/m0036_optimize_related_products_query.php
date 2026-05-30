<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_prod_attr_val_val_prod ON product_attribute_values(attribute_value_id, product_id);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_prod_attr_val_val_prod ON product_attribute_values(attribute_value_id, product_id);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_prod_attr_val_val_prod ON product_attribute_values;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_prod_attr_val_val_prod;
        ";
    }
};
