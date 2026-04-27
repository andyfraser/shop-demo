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

                INSERT IGNORE INTO `categories` (id, name, slug, parent_id, description, icon, created_at) VALUES 
                (1,'Electronics','electronics',NULL,'Gadgets, devices and tech','💻','2026-04-17 14:21:37'),
                (2,'Clothing','clothing',NULL,'Apparel for all occasions','👕','2026-04-17 14:21:37'),
                (3,'Home & Garden','home-garden',NULL,'For the home and garden','🏠','2026-04-17 14:21:37'),
                (4,'Laptops','laptops',1,'Portable computers','💻','2026-04-17 14:21:37'),
                (5,'Phones','phones',1,'Smartphones and accessories','📱','2026-04-17 14:21:37'),
                (6,'Audio','audio',1,'Headphones, speakers and more','🎧','2026-04-17 14:21:37'),
                (7,'Mens','mens',2,'Menswear','👔','2026-04-17 14:21:37'),
                (8,'Womens','womens',2,'Womenswear','👗','2026-04-17 14:21:37'),
                (9,'Kitchen','kitchen',3,'Kitchen appliances and tools','🍳','2026-04-17 14:21:37'),
                (10,'Garden Tools','garden-tools',3,'Tools for the garden','🌱','2026-04-17 14:21:37'),
                (14,'Gaming','gaming',1,'Gaming products.','🎮','2026-04-17 14:21:37'),
                (15,'Toys','toys',NULL,'Various toy categories.','🧸','2026-04-17 14:21:37'),
                (16,'Discovery Toys','discovery-toys',15,'Learning & discovery toys.','🧸','2026-04-17 14:21:37'),
                (17,'LEGO','lego',16,'All LEGO products.','🧸','2026-04-17 14:21:37'),
                (18,'Pet','pet',NULL,'Pet products.','🐈','2026-04-17 14:21:37'),
                (20,'Cat','cat',18,'Cat products.','🐈‍⬛','2026-04-17 14:21:37'),
                (21,'Dog','dog',18,'Dog products.','🦮','2026-04-17 14:21:37'),
                (22,'Other Animals','other-animals',18,'Products for other pets.','🐻','2026-04-17 14:21:37'),
                (23,'Books','books',NULL,'Books.','📚','2026-04-22 14:42:00'),
                (24,'Sports & Leisure','sports-leisure',NULL,'Sports equipment and leisure paraphernalia.','🏈','2026-04-22 14:51:51'),
                (25,'Sport','sport',24,'','⚾️','2026-04-22 14:52:29'),
                (26,'Leisure','leisure',24,'','♟️','2026-04-22 14:52:46');

                INSERT IGNORE INTO `products` (id, name, slug, description, price, stock, category_id, image, active, featured, created_at, vat_rate, sku, force_variant) VALUES 
                (1,'ProBook Laptop 15\"','probook-laptop-15','A powerful 15-inch laptop with 16GB RAM, 512GB SSD and a stunning display.',899.99,11,4,'img_69bd3779ba9bf9.94612358.jpg',1,0,'2026-04-17 14:21:37',20,'LAP-PROBK-15',0),
                (2,'UltraPhone X12','ultraphone-x12','The latest flagship smartphone.',749,20,5,'img_69bd376dc40e67.91131762.jpg',1,0,'2026-04-17 14:21:37',20,'PHN-UX12',0),
                (3,'Studio Wireless Headphones','studio-wireless-headphones','Premium over-ear headphones.',299.95,40,6,'img_69bd3762e90036.79611024.jpg',1,0,'2026-04-17 14:21:37',20,'AUD-STWIR',0),
                (4,'Classic Oxford Shirt','classic-oxford-shirt','Timeless Oxford weave cotton shirt.',59.99,79,7,'img_69bd3757beda69.95266902.jpg',1,0,'2026-04-17 14:21:37',20,'MEN-OXFRD',1),
                (5,'Merino Wool Jumper','merino-wool-jumper','Soft, lightweight and warm.',89,33,8,'img_69bd3728f36b86.03617973.jpg',1,0,'2026-04-17 14:21:37',20,'WOM-MERIN',0),
                (6,'Espresso Machine Pro','espresso-machine-pro','Barista-grade espresso at home.',449,11,9,'img_69bd371c0a7f91.86115081.jpg',1,0,'2026-04-17 14:21:37',20,'KIT-ESPRO',0),
                (7,'Carbon Steel Garden Trowel','carbon-steel-garden-trowel','Professional-grade carbon steel trowel.',24.99,59,10,'img_69bd370cad5f19.97444002.jpg',1,0,'2026-04-17 14:21:37',20,'GAR-TROWL',0),
                (8,'MiniBook Air 13\"','minibook-air-13','Featherlight 13-inch ultrabook.',1099,7,4,'img_69bd36fea24797.63486949.jpg',1,1,'2026-04-17 14:21:37',20,'LAP-MINIB-13',0),
                (9,'Bluetooth Speaker Cube','bluetooth-speaker-cube','Compact, waterproof Bluetooth speaker.',79.95,54,6,'img_69bd3678dda058.99673075.jpg',1,1,'2026-04-17 14:21:37',20,'AUD-BTCUB',0),
                (10,'Cast Iron Skillet 28cm','cast-iron-skillet-28cm','Pre-seasoned cast iron skillet.',39.99,26,9,'img_69bd39df6196a1.38026816.jpg',1,0,'2026-04-17 14:21:37',20,'KIT-SKILT-28',0),
                (11,'iPhone 17 Pro Max','iphone-17-pro-max','iPhone 17 Pro Max. The most powerful iPhone ever.',1199,2,5,'img_69bd46751926a3.56450672.webp',1,1,'2026-04-17 14:21:37',20,'PHN-I17PM',0),
                (12,'Playstation 5','playstation-5','PlayStation 5 Console.',479.99,0,14,'img_69bd4d700341b0.13942450.webp',1,0,'2026-04-17 14:21:37',20,'GAM-PS5',0),
                (13,'Nintendo Switch 2 Console','nintendo-switch-2-console','The next evolution of the Nintendo Switch console is here!',385.99,28,14,'img_69bd4ec03e4005.93093090.webp',1,0,'2026-04-17 14:21:37',20,'GAM-NSW2',0),
                (14,'LEGO Speed Champions Mercedes-AMG F1 W15 Race Car Toy','lego-speed-champions-mercedes-amg-f1-w15-race-car-toy','F1 fans and kids aged 10 and up can enjoy exciting race action.',23,11,17,'img_69bd50dc58de60.49794891.webp',1,0,'2026-04-17 14:21:37',20,'LEG-MERC-F1',0),
                (15,'LEGO Star Wars BB-8 Astromech Droid','lego-star-wars-bb-8-astromech-droid','STAR WARS LEGO DROID FIGURE.',80,9,17,'img_69bd54828d6ee5.27048989.webp',1,0,'2026-04-17 14:21:37',20,'LEG-BB8',0),
                (16,'Science Mad 20cm Illuminated Night Globe','science-mad-20cm-illuminated-night-globe','And you\'re off exploring the world!',22,0,16,'img_69bd5739b3ac33.55848488.webp',1,0,'2026-04-17 14:21:37',20,'DIS-GLOBE',0),
                (17,'Pokémon Mega Charizard X Ex Trading Card','pok-mon-mega-charizard-x-ex-trading-card','Mega Evolve!',22,0,15,'img_69bd57c1b0b871.21861055.webp',1,0,'2026-04-17 14:21:37',20,'TOY-PKMN-CHAR',0),
                (18,'SIM Free Samsung Galaxy S26 Ultra 5G 512GB AI Phone Violet','sim-free-samsung-galaxy-s26-ultra-5g-512gb-ai-phone-violet','Meet Galaxy S26 Ultra.',1449,1,5,'img_69bd589eb18a70.09464393.webp',1,0,'2026-04-17 14:21:37',20,'PHN-S26U-512',0),
                (19,'Petface House Scratcher','petface-house-scratcher','This Cat house scratcher.',70,13,20,'img_69bd632bc82e28.46608911.webp',1,0,'2026-04-17 14:21:37',20,'CAT-HOUSE',0),
                (20,'Cat Lounge and Play Scratcher','cat-lounge-and-play-scratcher','This cozy home.',88,6,20,'img_69bd63cfc64980.14864876.webp',1,0,'2026-04-17 14:21:37',20,'CAT-LOUNG',0),
                (21,'Ninja 7.6L Foodi Dual Zone Air Fryer and Dehydrator','ninja-7-6l-foodi-dual-zone-air-fryer-and-dehydrator','The air fryer.',200,21,9,'img_69cfa1d4e5f6c3.95570107.jpg',1,0,'2026-04-17 14:21:37',20,'KIT-NINJA-AF',0),
                (22,'McGregor 23cm Cordless Grass Trimmer - 18V','mcgregor-23cm-cordless-grass-trimmer-18v','This McGregor 18V 23cm grass trimmer.',36,12,10,'img_69d25436496576.37754497.jpg',1,0,'2026-04-17 14:21:37',20,'GAR-MCGR-TRIM',0),
                (23,'The Book','the-book','Hand-illustrated guide.',99,13,23,'img_69e8d00f114974.93305311.webp',1,1,'2026-04-22 14:41:35',0,NULL,0);

                INSERT IGNORE INTO `product_variants` (id, product_id, name, sku, price, stock, active, created_at) VALUES 
                (1,4,'Small','MEN-OXFRD-SMALL',NULL,12,1,'2026-04-27 13:48:16'),
                (2,4,'Medium','MEN-OXFRD-MEDIUM',NULL,20,1,'2026-04-27 14:00:38'),
                (3,4,'Large','MEN-OXFRD-LARGE',64.99,14,1,'2026-04-27 14:00:38'),
                (4,4,'Extra Large','MEN-OXFRD-XLARGE',64.99,0,1,'2026-04-27 14:01:11'),
                (5,9,'Blue','AUD-BTCUB-BLUE',NULL,4,1,'2026-04-27 14:04:49');

                INSERT IGNORE INTO `delivery_options` (id, name, price, active, min_order_total) VALUES 
                (1,'Standard Delivery',3.99,1,0),
                (2,'Next Day Delivery',6.99,1,0),
                (3,'Free Shipping (Over £50)',0,1,50);

                INSERT IGNORE INTO users (id, name, email, password_hash, role, is_verified) VALUES
                (1, 'Admin', 'admin@shop.local', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'admin', 1),
                (2, 'Jane Smith', 'jane@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'customer', 1);
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

                INSERT OR IGNORE INTO `categories` (id, name, slug, parent_id, description, icon, created_at) VALUES 
                (1,'Electronics','electronics',NULL,'Gadgets, devices and tech','💻','2026-04-17 14:21:37'),
                (2,'Clothing','clothing',NULL,'Apparel for all occasions','👕','2026-04-17 14:21:37'),
                (3,'Home & Garden','home-garden',NULL,'For the home and garden','🏠','2026-04-17 14:21:37'),
                (4,'Laptops','laptops',1,'Portable computers','💻','2026-04-17 14:21:37'),
                (5,'Phones','phones',1,'Smartphones and accessories','📱','2026-04-17 14:21:37'),
                (6,'Audio','audio',1,'Headphones, speakers and more','🎧','2026-04-17 14:21:37'),
                (7,'Mens','mens',2,'Menswear','👔','2026-04-17 14:21:37'),
                (8,'Womens','womens',2,'Womenswear','👗','2026-04-17 14:21:37'),
                (9,'Kitchen','kitchen',3,'Kitchen appliances and tools','🍳','2026-04-17 14:21:37'),
                (10,'Garden Tools','garden-tools',3,'Tools for the garden','🌱','2026-04-17 14:21:37'),
                (14,'Gaming','gaming',1,'Gaming products.','🎮','2026-04-17 14:21:37'),
                (15,'Toys','toys',NULL,'Various toy categories.','🧸','2026-04-17 14:21:37'),
                (16,'Discovery Toys','discovery-toys',15,'Learning & discovery toys.','🧸','2026-04-17 14:21:37'),
                (17,'LEGO','lego',16,'All LEGO products.','🧸','2026-04-17 14:21:37'),
                (18,'Pet','pet',NULL,'Pet products.','🐈','2026-04-17 14:21:37'),
                (20,'Cat','cat',18,'Cat products.','🐈‍⬛','2026-04-17 14:21:37'),
                (21,'Dog','dog',18,'Dog products.','🦮','2026-04-17 14:21:37'),
                (22,'Other Animals','other-animals',18,'Products for other pets.','🐻','2026-04-17 14:21:37'),
                (23,'Books','books',NULL,'Books.','📚','2026-04-22 14:42:00'),
                (24,'Sports & Leisure','sports-leisure',NULL,'Sports equipment and leisure paraphernalia.','🏈','2026-04-22 14:51:51'),
                (25,'Sport','sport',24,'','⚾️','2026-04-22 14:52:29'),
                (26,'Leisure','leisure',24,'','♟️','2026-04-22 14:52:46');

                INSERT OR IGNORE INTO `products` (id, name, slug, description, price, stock, category_id, image, active, featured, created_at, vat_rate, sku, force_variant) VALUES 
                (1,'ProBook Laptop 15\"','probook-laptop-15','A powerful 15-inch laptop with 16GB RAM, 512GB SSD and a stunning display.',899.99,11,4,'img_69bd3779ba9bf9.94612358.jpg',1,0,'2026-04-17 14:21:37',20,'LAP-PROBK-15',0),
                (2,'UltraPhone X12','ultraphone-x12','The latest flagship smartphone.',749,20,5,'img_69bd376dc40e67.91131762.jpg',1,0,'2026-04-17 14:21:37',20,'PHN-UX12',0),
                (3,'Studio Wireless Headphones','studio-wireless-headphones','Premium over-ear headphones.',299.95,40,6,'img_69bd3762e90036.79611024.jpg',1,0,'2026-04-17 14:21:37',20,'AUD-STWIR',0),
                (4,'Classic Oxford Shirt','classic-oxford-shirt','Timeless Oxford weave cotton shirt.',59.99,79,7,'img_69bd3757beda69.95266902.jpg',1,0,'2026-04-17 14:21:37',20,'MEN-OXFRD',1),
                (5,'Merino Wool Jumper','merino-wool-jumper','Soft, lightweight and warm.',89,33,8,'img_69bd3728f36b86.03617973.jpg',1,0,'2026-04-17 14:21:37',20,'WOM-MERIN',0),
                (6,'Espresso Machine Pro','espresso-machine-pro','Barista-grade espresso at home.',449,11,9,'img_69bd371c0a7f91.86115081.jpg',1,0,'2026-04-17 14:21:37',20,'KIT-ESPRO',0),
                (7,'Carbon Steel Garden Trowel','carbon-steel-garden-trowel','Professional-grade carbon steel trowel.',24.99,59,10,'img_69bd370cad5f19.97444002.jpg',1,0,'2026-04-17 14:21:37',20,'GAR-TROWL',0),
                (8,'MiniBook Air 13\"','minibook-air-13','Featherlight 13-inch ultrabook.',1099,7,4,'img_69bd36fea24797.63486949.jpg',1,1,'2026-04-17 14:21:37',20,'LAP-MINIB-13',0),
                (9,'Bluetooth Speaker Cube','bluetooth-speaker-cube','Compact, waterproof Bluetooth speaker.',79.95,54,6,'img_69bd3678dda058.99673075.jpg',1,1,'2026-04-17 14:21:37',20,'AUD-BTCUB',0),
                (10,'Cast Iron Skillet 28cm','cast-iron-skillet-28cm','Pre-seasoned cast iron skillet.',39.99,26,9,'img_69bd39df6196a1.38026816.jpg',1,0,'2026-04-17 14:21:37',20,'KIT-SKILT-28',0),
                (11,'iPhone 17 Pro Max','iphone-17-pro-max','iPhone 17 Pro Max. The most powerful iPhone ever.',1199,2,5,'img_69bd46751926a3.56450672.webp',1,1,'2026-04-17 14:21:37',20,'PHN-I17PM',0),
                (12,'Playstation 5','playstation-5','PlayStation 5 Console.',479.99,0,14,'img_69bd4d700341b0.13942450.webp',1,0,'2026-04-17 14:21:37',20,'GAM-PS5',0),
                (13,'Nintendo Switch 2 Console','nintendo-switch-2-console','The next evolution of the Nintendo Switch console is here!',385.99,28,14,'img_69bd4ec03e4005.93093090.webp',1,0,'2026-04-17 14:21:37',20,'GAM-NSW2',0),
                (14,'LEGO Speed Champions Mercedes-AMG F1 W15 Race Car Toy','lego-speed-champions-mercedes-amg-f1-w15-race-car-toy','F1 fans and kids aged 10 and up can enjoy exciting race action.',23,11,17,'img_69bd50dc58de60.49794891.webp',1,0,'2026-04-17 14:21:37',20,'LEG-MERC-F1',0),
                (15,'LEGO Star Wars BB-8 Astromech Droid','lego-star-wars-bb-8-astromech-droid','STAR WARS LEGO DROID FIGURE.',80,9,17,'img_69bd54828d6ee5.27048989.webp',1,0,'2026-04-17 14:21:37',20,'LEG-BB8',0),
                (16,'Science Mad 20cm Illuminated Night Globe','science-mad-20cm-illuminated-night-globe','And you''re off exploring the world!',22,0,16,'img_69bd5739b3ac33.55848488.webp',1,0,'2026-04-17 14:21:37',20,'DIS-GLOBE',0),
                (17,'Pokémon Mega Charizard X Ex Trading Card','pok-mon-mega-charizard-x-ex-trading-card','Mega Evolve!',22,0,15,'img_69bd57c1b0b871.21861055.webp',1,0,'2026-04-17 14:21:37',20,'TOY-PKMN-CHAR',0),
                (18,'SIM Free Samsung Galaxy S26 Ultra 5G 512GB AI Phone Violet','sim-free-samsung-galaxy-s26-ultra-5g-512gb-ai-phone-violet','Meet Galaxy S26 Ultra.',1449,1,5,'img_69bd589eb18a70.09464393.webp',1,0,'2026-04-17 14:21:37',20,'PHN-S26U-512',0),
                (19,'Petface House Scratcher','petface-house-scratcher','This Cat house scratcher.',70,13,20,'img_69bd632bc82e28.46608911.webp',1,0,'2026-04-17 14:21:37',20,'CAT-HOUSE',0),
                (20,'Cat Lounge and Play Scratcher','cat-lounge-and-play-scratcher','This cozy home.',88,6,20,'img_69bd63cfc64980.14864876.webp',1,0,'2026-04-17 14:21:37',20,'CAT-LOUNG',0),
                (21,'Ninja 7.6L Foodi Dual Zone Air Fryer and Dehydrator','ninja-7-6l-foodi-dual-zone-air-fryer-and-dehydrator','The air fryer.',200,21,9,'img_69cfa1d4e5f6c3.95570107.jpg',1,0,'2026-04-17 14:21:37',20,'KIT-NINJA-AF',0),
                (22,'McGregor 23cm Cordless Grass Trimmer - 18V','mcgregor-23cm-cordless-grass-trimmer-18v','This McGregor 18V 23cm grass trimmer.',36,12,10,'img_69d25436496576.37754497.jpg',1,0,'2026-04-17 14:21:37',20,'GAR-MCGR-TRIM',0),
                (23,'The Book','the-book','Hand-illustrated guide.',99,13,23,'img_69e8d00f114974.93305311.webp',1,1,'2026-04-22 14:41:35',0,NULL,0);

                INSERT OR IGNORE INTO `product_variants` (id, product_id, name, sku, price, stock, active, created_at) VALUES 
                (1,4,'Small','MEN-OXFRD-SMALL',NULL,12,1,'2026-04-27 13:48:16'),
                (2,4,'Medium','MEN-OXFRD-MEDIUM',NULL,20,1,'2026-04-27 14:00:38'),
                (3,4,'Large','MEN-OXFRD-LARGE',64.99,14,1,'2026-04-27 14:00:38'),
                (4,4,'Extra Large','MEN-OXFRD-XLARGE',64.99,0,1,'2026-04-27 14:01:11'),
                (5,9,'Blue','AUD-BTCUB-BLUE',NULL,4,1,'2026-04-27 14:04:49');

                INSERT OR IGNORE INTO `delivery_options` (id, name, price, active, min_order_total) VALUES 
                (1,'Standard Delivery',3.99,1,0),
                (2,'Next Day Delivery',6.99,1,0),
                (3,'Free Shipping (Over £50)',0,1,50);

                INSERT OR IGNORE INTO users (id, name, email, password_hash, role, is_verified) VALUES
                (1, 'Admin', 'admin@shop.local', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'admin', 1),
                (2, 'Jane Smith', 'jane@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'customer', 1);
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
