<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_promotions_active_dates_priority ON promotions(active, start_date, end_date, priority, value);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_promotions_active_dates_priority ON promotions(active, start_date, end_date, priority, value);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_promotions_active_dates_priority ON promotions;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_promotions_active_dates_priority;
        ";
    }
};
