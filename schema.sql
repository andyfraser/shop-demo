-- E-Commerce Schema

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
    description TEXT,
    price REAL NOT NULL,
    stock INTEGER DEFAULT 0,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    image TEXT,
    active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'customer',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id),
    status TEXT DEFAULT 'pending',
    total REAL NOT NULL,
    shipping_address TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id),
    quantity INTEGER NOT NULL,
    unit_price REAL NOT NULL
);

-- Seed Categories
INSERT OR IGNORE INTO categories (id, name, slug, parent_id, description, icon) VALUES
(1, 'Electronics', 'electronics', NULL, 'Gadgets, devices and tech', '💻'),
(2, 'Clothing', 'clothing', NULL, 'Apparel for all occasions', '👕'),
(3, 'Home & Garden', 'home-garden', NULL, 'For the home and garden', '🏠'),
(4, 'Laptops', 'laptops', 1, 'Portable computers', '💻'),
(5, 'Phones', 'phones', 1, 'Smartphones and accessories', '📱'),
(6, 'Audio', 'audio', 1, 'Headphones, speakers and more', '🎧'),
(7, 'Mens', 'mens', 2, 'Menswear', '👔'),
(8, 'Womens', 'womens', 2, 'Womenswear', '👗'),
(9, 'Kitchen', 'kitchen', 3, 'Kitchen appliances and tools', '🍳'),
(10, 'Garden Tools', 'garden-tools', 3, 'Tools for the garden', '🌱');

-- Seed Products
INSERT OR IGNORE INTO products (id, name, slug, description, price, stock, category_id, image, active) VALUES
(1, 'ProBook Laptop 15"', 'probook-laptop-15', 'A powerful 15-inch laptop with 16GB RAM, 512GB SSD and a stunning display. Perfect for work and creativity.', 899.99, 14, 4, 'probook-laptop-15.jpg', 1),
(2, 'UltraPhone X12', 'ultraphone-x12', 'The latest flagship smartphone featuring a triple-camera system, 5G connectivity and all-day battery life.', 749.00, 28, 5, 'ultraphone-x12.jpg', 1),
(3, 'Studio Wireless Headphones', 'studio-wireless-headphones', 'Premium over-ear headphones with active noise cancellation and 30-hour battery. Studio-quality sound anywhere.', 299.95, 45, 6, 'studio-wireless-headphones.jpg', 1),
(4, 'Classic Oxford Shirt', 'classic-oxford-shirt', 'Timeless Oxford weave cotton shirt. Versatile enough for the office or weekend. Available in multiple colours.', 59.99, 80, 7, 'classic-oxford-shirt.jpg', 1),
(5, 'Merino Wool Jumper', 'merino-wool-jumper', 'Soft, lightweight and warm. This merino wool jumper is a wardrobe essential for the cooler months.', 89.00, 35, 8, 'merino-wool-jumper.jpg', 1),
(6, 'Espresso Machine Pro', 'espresso-machine-pro', 'Barista-grade espresso at home. 15-bar pump pressure, built-in grinder, and milk frother included.', 449.00, 12, 9, 'espresso-machine-pro.jpg', 1),
(7, 'Carbon Steel Garden Trowel', 'carbon-steel-garden-trowel', 'Professional-grade carbon steel trowel with an ergonomic hardwood handle. Built to last a lifetime.', 24.99, 60, 10, 'carbon-steel-garden-trowel.jpg', 1),
(8, 'MiniBook Air 13"', 'minibook-air-13', 'Featherlight 13-inch ultrabook. All-day battery, fanless design, and a gorgeous Retina-class display.', 1099.00, 8, 4, 'minibook-air-13.jpg', 1),
(9, 'Bluetooth Speaker Cube', 'bluetooth-speaker-cube', 'Compact, waterproof Bluetooth speaker delivering surprisingly big sound. Perfect for outdoors.', 79.95, 55, 6, 'bluetooth-speaker-cube.jpg', 1),
(10, 'Cast Iron Skillet 28cm', 'cast-iron-skillet-28cm', 'Pre-seasoned cast iron skillet. Sears, bakes, grills and fries. Virtually indestructible.', 39.99, 30, 9, 'cast-iron-skillet-28cm.jpg', 1);

-- Users are seeded in bootstrap.php so passwords are properly hashed at runtime
