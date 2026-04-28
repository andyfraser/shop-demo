<?php

return new class {
    public function up(string $driver): string {
        return "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL;
                ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending';
                ALTER TABLE orders ADD COLUMN payment_transaction_id VARCHAR(255) DEFAULT NULL;";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE orders DROP COLUMN payment_method;
                    ALTER TABLE orders DROP COLUMN payment_status;
                    ALTER TABLE orders DROP COLUMN payment_transaction_id;";
        }
        
        // SQLite doesn't support DROP COLUMN easily in older versions
        return "";
    }
};
