<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE product_variants ADD COLUMN sort_order INT DEFAULT 0 AFTER active;";
        } else {
            return "ALTER TABLE product_variants ADD COLUMN sort_order INTEGER DEFAULT 0;";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE product_variants DROP COLUMN sort_order;";
        } else {
            return "ALTER TABLE product_variants DROP COLUMN sort_order;";
        }
    }
};
