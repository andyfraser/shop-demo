<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_promotions_active_dates_priority ON promotions;
                CREATE INDEX idx_promotions_active_priority_value_dates ON promotions(active, priority DESC, value DESC, start_date, end_date);
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_promotions_active_dates_priority;
            CREATE INDEX IF NOT EXISTS idx_promotions_active_priority_value_dates ON promotions(active, priority DESC, value DESC, start_date, end_date);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_promotions_active_priority_value_dates ON promotions;
                CREATE INDEX idx_promotions_active_dates_priority ON promotions(active, start_date, end_date, priority, value);
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_promotions_active_priority_value_dates;
            CREATE INDEX IF NOT EXISTS idx_promotions_active_dates_priority ON promotions(active, start_date, end_date, priority, value);
        ";
    }
};
