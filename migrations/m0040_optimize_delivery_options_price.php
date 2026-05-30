<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_delivery_options_price ON delivery_options(price);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_delivery_options_price ON delivery_options(price);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_delivery_options_price ON delivery_options;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_delivery_options_price;
        ";
    }
};
