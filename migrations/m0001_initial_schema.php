<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    parent_id INT REFERENCES categories(id) ON DELETE SET NULL,
                    description TEXT,
                    icon VARCHAR(50),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    sku VARCHAR(255) UNIQUE,
                    description TEXT,
                    price DOUBLE NOT NULL,
                    vat_rate DOUBLE DEFAULT 20.0,
                    stock INT DEFAULT 0,
                    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
                    image VARCHAR(255),
                    active TINYINT(1) DEFAULT 1,
                    featured TINYINT(1) DEFAULT 0,
                    force_variant TINYINT(1) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) DEFAULT 'customer',
                    is_verified TINYINT(1) DEFAULT 0,
                    verification_token TEXT,
                    address TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT REFERENCES users(id),
                    status VARCHAR(50) DEFAULT 'pending',
                    total DOUBLE NOT NULL,
                    total_vat_amount DOUBLE DEFAULT 0.0,
                    shipping_address TEXT,
                    notes TEXT,
                    delivery_method VARCHAR(255),
                    delivery_cost DOUBLE DEFAULT 0,
                    customer_email VARCHAR(255),
                    customer_name VARCHAR(255),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS delivery_options (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    price DOUBLE NOT NULL,
                    active TINYINT(1) DEFAULT 1,
                    min_order_total DOUBLE DEFAULT 0
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS product_variants (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    sku VARCHAR(255) UNIQUE,
                    price DOUBLE,
                    stock INT DEFAULT 0,
                    active TINYINT(1) DEFAULT 1,
                    sort_order INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT REFERENCES orders(id) ON DELETE CASCADE,
                    product_id INT REFERENCES products(id),
                    variant_id INT REFERENCES product_variants(id) ON DELETE SET NULL,
                    quantity INT NOT NULL,
                    unit_price DOUBLE NOT NULL,
                    vat_rate DOUBLE DEFAULT 0.0,
                    vat_amount DOUBLE DEFAULT 0.0
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS settings (
                    `key` VARCHAR(255) PRIMARY KEY,
                    value TEXT NOT NULL
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS remember_tokens (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    user_id    INT NOT NULL,
                    token      VARCHAR(64) NOT NULL UNIQUE,
                    expires_at INT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB;

                CREATE INDEX idx_categories_parent ON categories(parent_id);
                CREATE INDEX idx_products_category_active ON products(category_id, active);
                CREATE INDEX idx_orders_user_created ON orders(user_id, created_at);
                CREATE INDEX idx_orders_status_created ON orders(status, created_at);
                CREATE INDEX idx_order_items_order ON order_items(order_id);
                CREATE INDEX idx_order_items_product ON order_items(product_id);
                CREATE INDEX idx_users_role ON users(role);
                CREATE INDEX idx_rate_limits_lookup ON rate_limits(action, ip_address, created_at);
            ";
        } else {
            // SQLite schema and data
            return "
                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
                    description TEXT,
                    icon TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    sku TEXT UNIQUE,
                    description TEXT,
                    price REAL NOT NULL,
                    vat_rate REAL DEFAULT 20.0,
                    stock INTEGER DEFAULT 0,
                    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
                    image TEXT,
                    active INTEGER DEFAULT 1,
                    featured INTEGER DEFAULT 0,
                    force_variant INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    password_hash TEXT NOT NULL,
                    role TEXT DEFAULT 'customer',
                    is_verified INTEGER DEFAULT 0,
                    verification_token TEXT,
                    address TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER REFERENCES users(id),
                    status TEXT DEFAULT 'pending',
                    total REAL NOT NULL,
                    total_vat_amount REAL DEFAULT 0.0,
                    shipping_address TEXT,
                    notes TEXT,
                    delivery_method TEXT,
                    delivery_cost REAL DEFAULT 0,
                    customer_email TEXT,
                    customer_name TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS delivery_options (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    price REAL NOT NULL,
                    active INTEGER DEFAULT 1,
                    min_order_total REAL DEFAULT 0
                );

                CREATE TABLE IF NOT EXISTS order_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
                    product_id INTEGER REFERENCES products(id),
                    variant_id INTEGER REFERENCES product_variants(id) ON DELETE SET NULL,
                    quantity INTEGER NOT NULL,
                    unit_price REAL NOT NULL,
                    vat_rate REAL DEFAULT 0.0,
                    vat_amount REAL DEFAULT 0.0
                );

                CREATE TABLE IF NOT EXISTS product_variants (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                    name TEXT NOT NULL,
                    sku TEXT UNIQUE,
                    price REAL,
                    stock INTEGER DEFAULT 0,
                    active INTEGER DEFAULT 1,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    action TEXT NOT NULL,
                    ip_address TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS settings (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL
                );

                CREATE TABLE IF NOT EXISTS remember_tokens (
                    id         INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    token      TEXT NOT NULL UNIQUE,
                    expires_at INTEGER NOT NULL
                );

                CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_id);
                CREATE INDEX IF NOT EXISTS idx_products_category_active ON products(category_id, active);
                CREATE INDEX IF NOT EXISTS idx_orders_user_created ON orders(user_id, created_at);
                CREATE INDEX IF NOT EXISTS idx_orders_status_created ON orders(status, created_at);
                CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);
                CREATE INDEX IF NOT EXISTS idx_order_items_product ON order_items(product_id);
                CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
                CREATE INDEX IF NOT EXISTS idx_rate_limits_lookup ON rate_limits(action, ip_address, created_at);
            ";
        }
    }

    public function down(string $driver): string {
        return "
            DROP TABLE IF EXISTS remember_tokens;
            DROP TABLE IF EXISTS settings;
            DROP TABLE IF EXISTS rate_limits;
            DROP TABLE IF EXISTS order_items;
            DROP TABLE IF EXISTS product_variants;
            DROP TABLE IF EXISTS delivery_options;
            DROP TABLE IF EXISTS orders;
            DROP TABLE IF EXISTS users;
            DROP TABLE IF EXISTS products;
            DROP TABLE IF EXISTS categories;
        ";
    }
};
