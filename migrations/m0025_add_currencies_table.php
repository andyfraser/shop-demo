<?php

return new class {
    public function up(string $driver): string {
        $pk = ($driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = ($driver === 'mysql') ? ' ENGINE=InnoDB' : '';
        $decimal = ($driver === 'mysql') ? 'DECIMAL(10, 4)' : 'DECIMAL(10, 4)'; // SQLite handles decimals as reals

        return "
            CREATE TABLE IF NOT EXISTS currencies (
                id $pk,
                code VARCHAR(3) NOT NULL UNIQUE,
                name VARCHAR(50) NOT NULL,
                symbol VARCHAR(10) NOT NULL,
                exchange_rate $decimal NOT NULL DEFAULT 1.0000,
                is_base TINYINT(1) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )$engine;

            CREATE INDEX idx_currencies_code ON currencies(code);
            CREATE INDEX idx_currencies_active ON currencies(active);

            INSERT INTO currencies (code, name, symbol, exchange_rate, is_base, active) VALUES 
            ('GBP', 'British Pound', '£', 1.0000, 1, 1),
            ('USD', 'US Dollar', '$', 1.2600, 0, 1),
            ('EUR', 'Euro', '€', 1.1600, 0, 1);
        ";
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS currencies";
    }
};
