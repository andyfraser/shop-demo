<?php

return new class {
    public function up(string $driver): string {
        // Password is 'password'
        $passwordHash = '$2y$12$/WhyIBs7A2rzo0.FPS205OWucmgQJ5TY.NXS6mUIq7cY0NW/mNW7G';
        
        if ($driver === 'mysql') {
            return "
                INSERT IGNORE INTO users (id, name, email, password_hash, role, is_verified) 
                VALUES (1, 'Admin', 'admin@shop.local', '{$passwordHash}', 'admin', 1);
            ";
        }

        return "
            INSERT OR IGNORE INTO users (id, name, email, password_hash, role, is_verified) 
            VALUES (1, 'Admin', 'admin@shop.local', '{$passwordHash}', 'admin', 1);
        ";
    }

    public function down(string $driver): string {
        return "DELETE FROM users WHERE email = 'admin@shop.local' AND role = 'admin';";
    }
};
