<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE attribute_values ADD COLUMN sort_order INT DEFAULT 0;";
        } else {
            return "ALTER TABLE attribute_values ADD COLUMN sort_order INTEGER DEFAULT 0;";
        }
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "ALTER TABLE attribute_values DROP COLUMN sort_order;";
        } else {
            // SQLite doesn't support DROP COLUMN easily before 3.35.0, 
            // but for simplicity in this demo we'll just use a comment or ignore.
            return "-- DROP COLUMN not supported in older SQLite";
        }
    }
};
