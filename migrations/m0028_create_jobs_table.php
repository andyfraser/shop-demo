<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS jobs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    handler_class TEXT NOT NULL,
                    payload LONGTEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    attempts INT NOT NULL DEFAULT 0,
                    error LONGTEXT,
                    created_at DATETIME NOT NULL,
                    started_at DATETIME,
                    finished_at DATETIME,
                    INDEX idx_jobs_status (status)
                ) ENGINE=InnoDB;
            ";
        }

        return "
            CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                handler_class TEXT NOT NULL,
                payload TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                attempts INTEGER NOT NULL DEFAULT 0,
                error TEXT,
                created_at DATETIME NOT NULL,
                started_at DATETIME,
                finished_at DATETIME
            );
            CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status);
        ";
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS jobs";
    }
};
