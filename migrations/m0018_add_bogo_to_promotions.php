<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE promotions ADD COLUMN buy_qty INT DEFAULT NULL;
                ALTER TABLE promotions ADD COLUMN get_qty INT DEFAULT NULL;
            ";
        } else {
            return "
                ALTER TABLE promotions ADD COLUMN buy_qty INTEGER DEFAULT NULL;
                ALTER TABLE promotions ADD COLUMN get_qty INTEGER DEFAULT NULL;
            ";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE promotions DROP COLUMN buy_qty;
                ALTER TABLE promotions DROP COLUMN get_qty;
            ";
        } else {
            // SQLite doesn't support DROP COLUMN easily before 3.35.0
            return "SELECT 1;";
        }
    }
};
