<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_products_active_featured_created ON products(active, featured, created_at);
                CREATE INDEX idx_product_variants_stock ON product_variants(product_id, active, stock);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_products_active_featured_created ON products(active, featured, created_at);
            CREATE INDEX IF NOT EXISTS idx_product_variants_stock ON product_variants(product_id, active, stock);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_products_active_featured_created ON products;
                DROP INDEX idx_product_variants_stock ON product_variants;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_products_active_featured_created;
            DROP INDEX IF EXISTS idx_product_variants_stock;
        ";
    }
};
