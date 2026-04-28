<?php

return new class {
    public function up(string $driver): string {
        $sql = "ALTER TABLE orders ADD COLUMN refund_status VARCHAR(20) DEFAULT NULL;
                ALTER TABLE orders ADD COLUMN refunded_amount DECIMAL(10, 2) DEFAULT 0.00;";

        if ($driver === 'mysql') {
            $sql .= "CREATE TABLE returns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                user_id INT DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                reason TEXT,
                refund_amount DECIMAL(10, 2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE return_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                return_id INT NOT NULL,
                order_item_id INT NOT NULL,
                quantity INT NOT NULL,
                FOREIGN KEY (return_id) REFERENCES returns(id),
                FOREIGN KEY (order_item_id) REFERENCES order_items(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        } else {
            $sql .= "CREATE TABLE returns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                user_id INTEGER DEFAULT NULL,
                status TEXT DEFAULT 'pending',
                reason TEXT,
                refund_amount REAL DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE return_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                return_id INTEGER NOT NULL,
                order_item_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL
            );";
        }

        return $sql;
    }

    public function down(string $driver): string {
        $sql = "DROP TABLE IF EXISTS return_items;
                DROP TABLE IF EXISTS returns;";
        
        if ($driver === 'mysql') {
            $sql .= "ALTER TABLE orders DROP COLUMN refund_status;
                    ALTER TABLE orders DROP COLUMN refunded_amount;";
        }

        return $sql;
    }
};
