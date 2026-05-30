<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_wishlists_user_product ON wishlists(user_id, product_id);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_wishlists_user_product ON wishlists(user_id, product_id);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_wishlists_user_product ON wishlists;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_wishlists_user_product;
        ";
    }
};
