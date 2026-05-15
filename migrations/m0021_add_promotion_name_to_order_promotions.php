<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE order_promotions ADD COLUMN promotion_name VARCHAR(255) AFTER promotion_id;
            ";
        } else {
            return "
                ALTER TABLE order_promotions ADD COLUMN promotion_name TEXT;
            ";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE order_promotions DROP COLUMN promotion_name;
            ";
        } else {
            // SQLite doesn't support DROP COLUMN easily
            return "";
        }
    }
};
