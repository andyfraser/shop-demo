<?php

return new class {
    public function up(string $driver): string {
        return "ALTER TABLE orders ADD COLUMN gift_card_amount DOUBLE DEFAULT 0.0;";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE orders DROP COLUMN gift_card_amount;";
        }
        return "";
    }
};
