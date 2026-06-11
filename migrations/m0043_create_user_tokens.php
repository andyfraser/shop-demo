<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS `user_tokens` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `created_at` DATETIME NOT NULL,
                    `expires_at` DATETIME NULL,
                    `last_used_at` DATETIME NULL,
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
        }
        return "
            CREATE TABLE IF NOT EXISTS `user_tokens` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `token_hash` VARCHAR(64) NOT NULL UNIQUE,
                `created_at` DATETIME NOT NULL,
                `expires_at` DATETIME NULL,
                `last_used_at` DATETIME NULL,
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            );
        ";
    }

    public function down(string $driver): string {
        return "
            DROP TABLE IF EXISTS `user_tokens`;
        ";
    }
};
