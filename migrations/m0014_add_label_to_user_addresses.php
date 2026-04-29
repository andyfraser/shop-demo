<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE user_addresses ADD COLUMN label VARCHAR(100) AFTER user_id;";
        } else {
            return "ALTER TABLE user_addresses ADD COLUMN label TEXT;";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE user_addresses DROP COLUMN label;";
        } else {
            // SQLite doesn't support DROP COLUMN in older versions, but here we can just leave it or recreate.
            // For simplicity in this demo environment:
            return "SELECT 1;"; 
        }
    }
};
