<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_reviews_product_status_created ON reviews(product_id, status, created_at);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_reviews_product_status_created ON reviews(product_id, status, created_at);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_reviews_product_status_created ON reviews;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_reviews_product_status_created;
        ";
    }
};
