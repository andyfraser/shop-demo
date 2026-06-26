<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_products_slug_active ON products(slug, active);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_products_slug_active ON products(slug, active);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_products_slug_active ON products;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_products_slug_active;
        ";
    }
};
