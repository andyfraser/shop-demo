<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_cart_promotions_cart_id ON cart_promotions(cart_id);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_cart_promotions_cart_id ON cart_promotions(cart_id);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_cart_promotions_cart_id ON cart_promotions;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_cart_promotions_cart_id;
        ";
    }
};
