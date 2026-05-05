<?php

return new class {
    public function up(string $driver): string {
        // Normalize existing dates that might contain 'T' from datetime-local inputs
        if ($driver === 'mysql') {
            return "
                UPDATE promotions 
                SET start_date = REPLACE(start_date, 'T', ' ') 
                WHERE start_date LIKE '%T%';

                UPDATE promotions 
                SET end_date = REPLACE(end_date, 'T', ' ') 
                WHERE end_date LIKE '%T%';
            ";
        } else {
            return "
                UPDATE promotions 
                SET start_date = REPLACE(start_date, 'T', ' ') 
                WHERE start_date LIKE '%T%';

                UPDATE promotions 
                SET end_date = REPLACE(end_date, 'T', ' ') 
                WHERE end_date LIKE '%T%';
            ";
        }
    }

    public function down(string $driver): string {
        return ""; // No rollback for normalization
    }
};
