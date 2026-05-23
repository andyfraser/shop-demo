<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "CREATE INDEX idx_product_variants_lookup ON product_variants(product_id, active, sort_order, name);";
        }
        return "CREATE INDEX IF NOT EXISTS idx_product_variants_lookup ON product_variants(product_id, active, sort_order, name);";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "DROP INDEX idx_product_variants_lookup ON product_variants;";
        }
        return "DROP INDEX IF EXISTS idx_product_variants_lookup;";
    }
};
