<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE jobs ADD COLUMN available_at DATETIME AFTER created_at;
                UPDATE jobs SET available_at = created_at;
                ALTER TABLE jobs MODIFY COLUMN available_at DATETIME NOT NULL;
                CREATE INDEX idx_jobs_available_at ON jobs(available_at);
            ";
        }

        return "
            ALTER TABLE jobs ADD COLUMN available_at DATETIME;
            UPDATE jobs SET available_at = created_at;
            CREATE INDEX IF NOT EXISTS idx_jobs_available_at ON jobs(available_at);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_jobs_available_at ON jobs;
                ALTER TABLE jobs DROP COLUMN available_at;
            ";
        }

        return "
            ALTER TABLE jobs DROP COLUMN available_at;
        ";
    }
};
