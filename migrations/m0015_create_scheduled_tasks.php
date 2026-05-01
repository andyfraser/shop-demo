<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS scheduled_tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL UNIQUE,
                    last_run_at DATETIME DEFAULT NULL,
                    INDEX (name)
                ) ENGINE=InnoDB;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS scheduled_tasks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE,
                    last_run_at DATETIME DEFAULT NULL
                );
                CREATE UNIQUE INDEX idx_scheduled_tasks_name ON scheduled_tasks(name);
            ";
        }
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS scheduled_tasks;";
    }
};
