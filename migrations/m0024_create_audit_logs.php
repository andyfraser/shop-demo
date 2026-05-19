<?php

return new class {
    public function up(string $driver): string {
        $pk = ($driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = ($driver === 'mysql') ? ' ENGINE=InnoDB' : '';
        
        return "
            CREATE TABLE IF NOT EXISTS audit_logs (
                id $pk,
                user_id INTEGER NULL,
                action VARCHAR(255) NOT NULL,
                resource_type VARCHAR(255) NULL,
                resource_id VARCHAR(255) NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )$engine;
            
            CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
            CREATE INDEX idx_audit_logs_action ON audit_logs(action);
        ";
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS audit_logs";
    }
};
