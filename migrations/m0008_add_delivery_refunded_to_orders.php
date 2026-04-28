<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE orders ADD COLUMN delivery_refunded TINYINT(1) DEFAULT 0 AFTER delivery_cost;";
        } else {
            return "ALTER TABLE orders ADD COLUMN delivery_refunded INTEGER DEFAULT 0;";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE orders DROP COLUMN delivery_refunded;";
        } else {
            // SQLite doesn't support DROP COLUMN in older versions, 
            // but for a demo app we usually just leave it or recreate the table.
            return ""; 
        }
    }
};
