<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS user_roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                INSERT IGNORE INTO user_roles (name, slug, description) VALUES 
                ('Customer', 'customer', 'Standard customer role'),
                ('VIP', 'vip', 'Special VIP customers'),
                ('Wholesale', 'wholesale', 'Wholesale business customers');
            ";
        }

        return "
            CREATE TABLE IF NOT EXISTS user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            INSERT OR IGNORE INTO user_roles (name, slug, description) VALUES 
            ('Customer', 'customer', 'Standard customer role'),
            ('VIP', 'vip', 'Special VIP customers'),
            ('Wholesale', 'wholesale', 'Wholesale business customers');
        ";
    }

    public function down(string $driver): string {
        return "DROP TABLE IF EXISTS user_roles;";
    }
};
