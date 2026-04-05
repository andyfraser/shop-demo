-- E-Commerce Schema (MySQL)

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
    description TEXT,
    price DOUBLE NOT NULL,
    stock INT DEFAULT 0,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    image VARCHAR(255),
    active TINYINT(1) DEFAULT 1,
    featured TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'customer',
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'pending',
    total DOUBLE NOT NULL,
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

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT REFERENCES orders(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id),
    quantity INT NOT NULL,
    unit_price DOUBLE NOT NULL
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

-- Indexes
CREATE INDEX idx_categories_parent ON categories(parent_id);
CREATE INDEX idx_products_category_active ON products(category_id, active);
CREATE INDEX idx_orders_user_created ON orders(user_id, created_at);
CREATE INDEX idx_orders_status_created ON orders(status, created_at);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_rate_limits_lookup ON rate_limits(action, ip_address, created_at);

-- Seed Categories
INSERT IGNORE INTO categories (id, name, slug, parent_id, description, icon) VALUES
(1, 'Electronics', 'electronics', NULL, 'Gadgets, devices and tech', '💻'),
(2, 'Clothing', 'clothing', NULL, 'Apparel for all occasions', '👕'),
(3, 'Home & Garden', 'home-garden', NULL, 'For the home and garden', '🏠'),
(4, 'Laptops', 'laptops', 1, 'Portable computers', '💻'),
(5, 'Phones', 'phones', 1, 'Smartphones and accessories', '📱'),
(6, 'Audio', 'audio', 1, 'Headphones, speakers and more', '🎧'),
(7, 'Mens', 'mens', 2, 'Menswear', '👔'),
(8, 'Womens', 'womens', 2, 'Womenswear', '👗'),
(9, 'Kitchen', 'kitchen', 3, 'Kitchen appliances and tools', '🍳'),
(10, 'Garden Tools', 'garden-tools', 3, 'Tools for the garden', '🌱'),
(14, 'Gaming', 'gaming', 1, 'Gaming products.', '🎮'),
(15, 'Toys', 'toys', NULL, 'Various toy categories.', '🧸'),
(16, 'Discovery Toys', 'discovery-toys', 15, 'Learning & discovery toys.', '🧸'),
(17, 'LEGO', 'lego', 16, 'All LEGO products.', '🧸'),
(18, 'Pet', 'pet', NULL, 'Pet products.', '🐈'),
(20, 'Cat', 'cat', 18, 'Cat products.', '🐈‍⬛'),
(21, 'Dog', 'dog', 18, 'Dog products.', '🦮'),
(22, 'Other Animals', 'other-animals', 18, 'Products for other pets.', '🐻');

-- Seed Products
INSERT IGNORE INTO products (id, name, slug, description, price, stock, category_id, image, active) VALUES
(1, 'ProBook Laptop 15"', 'probook-laptop-15', 'A powerful 15-inch laptop with 16GB RAM, 512GB SSD and a stunning display. Perfect for work and creativity.', 899.99, 11, 4, 'img_69bd3779ba9bf9.94612358.jpg', 1),
(2, 'UltraPhone X12', 'ultraphone-x12', 'The latest flagship smartphone featuring a triple-camera system, 5G connectivity and all-day battery life.', 749.00, 27, 5, 'img_69bd376dc40e67.91131762.jpg', 1),
(3, 'Studio Wireless Headphones', 'studio-wireless-headphones', 'Premium over-ear headphones with active noise cancellation and 30-hour battery. Studio-quality sound anywhere.', 299.95, 43, 6, 'img_69bd3762e90036.79611024.jpg', 1),
(4, 'Classic Oxford Shirt', 'classic-oxford-shirt', 'Timeless Oxford weave cotton shirt. Versatile enough for the office or weekend. Available in multiple colours.', 59.99, 80, 7, 'img_69bd3757beda69.95266902.jpg', 1),
(5, 'Merino Wool Jumper', 'merino-wool-jumper', 'Soft, lightweight and warm. This merino wool jumper is a wardrobe essential for the cooler months.', 89.00, 34, 8, 'img_69bd3728f36b86.03617973.jpg', 1),
(6, 'Espresso Machine Pro', 'espresso-machine-pro', 'Barista-grade espresso at home. 15-bar pump pressure, built-in grinder, and milk frother included.', 449.00, 12, 9, 'img_69bd371c0a7f91.86115081.jpg', 1),
(7, 'Carbon Steel Garden Trowel', 'carbon-steel-garden-trowel', 'Professional-grade carbon steel trowel with an ergonomic hardwood handle. Built to last a lifetime.', 24.99, 60, 10, 'img_69bd370cad5f19.97444002.jpg', 1),
(8, 'MiniBook Air 13"', 'minibook-air-13', 'Featherlight 13-inch ultrabook. All-day battery, fanless design, and a gorgeous Retina-class display.', 1099.00, 7, 4, 'img_69bd36fea24797.63486949.jpg', 1),
(9, 'Bluetooth Speaker Cube', 'bluetooth-speaker-cube', 'Compact, waterproof Bluetooth speaker delivering surprisingly big sound. Perfect for outdoors.', 79.95, 55, 6, 'img_69bd3678dda058.99673075.jpg', 1),
(10, 'Cast Iron Skillet 28cm', 'cast-iron-skillet-28cm', 'Pre-seasoned cast iron skillet. Sears, bakes, grills and fries. Virtually indestructible.', 39.99, 30, 9, 'img_69bd39df6196a1.38026816.jpg', 1),
(11, 'iPhone 17 Pro Max', 'iphone-17-pro-max', 'iPhone 17 Pro Max. The most powerful iPhone ever. Brilliant 6.9-inch display, aluminium unibody design, A19 Pro chip, all 48MP rear cameras and best-ever battery life. UNIBODY DESIGN. FOR EXCEPTIONAL POWER - Heat-forged aluminium unibody design for the most powerful iPhone ever made. DURABLE CERAMIC SHIELD. FRONT AND BACK - Ceramic Shield protects the back of iPhone 17 Pro Max, making it 4x more resistant to cracks. And the new Ceramic Shield 2 on the front has 3x better scratch resistance.', 1199.00, 2, 5, 'img_69bd46751926a3.56450672.webp', 1),
(12, 'Playstation 5', 'playstation-5', 'PlayStation 5 Console. The PS5 console unleashes new gaming possibilities that you never anticipated. Experience lightning-fast loading with an ultra-high speed SSD, deeper immersion with support for haptic feedback, adaptive triggers, and 3D Audio, and an all-new generation of incredible PlayStation games.', 479.99, 0, 14, 'img_69bd4d700341b0.13942450.webp', 1),
(13, 'Nintendo Switch 2 Console', 'nintendo-switch-2-console', 'The next evolution of the Nintendo Switch console is here!\n\nBring games to life with a larger 1080p screen—or connect to a TV and play in up to 4K resolution. Support for HDR, VRR, and frame rates up to 120 fps lets you enjoy vivid colour, clarity, and smooth gameplay. Snap the new Joy-Con 2 controllers into place with magnetic connectors. Each controller can even be used as a mouse in compatible games.', 385.99, 28, 14, 'img_69bd4ec03e4005.93093090.webp', 1),
(14, 'LEGO Speed Champions Mercedes-AMG F1 W15 Race Car Toy', 'lego-speed-champions-mercedes-amg-f1-w15-race-car-toy', 'F1 fans and kids aged 10 and up can enjoy exciting race action with this LEGO Speed Champions Mercedes-AMG PETRONAS F1 Team toy car.', 23.00, 11, 17, 'img_69bd50dc58de60.49794891.webp', 1),
(15, 'LEGO Star Wars BB-8 Astromech Droid', 'lego-star-wars-bb-8-astromech-droid', 'STAR WARS LEGO DROID FIGURE – Inspire kids to create the LEGO Star Wars BB-8 Astromech Droid toy for 10+ year old boys and girls, capturing the charm of the character from The Force Awakens.', 80.00, 9, 17, 'img_69bd54828d6ee5.27048989.webp', 1),
(16, 'Science Mad 20cm Illuminated Night Globe', 'science-mad-20cm-illuminated-night-globe', 'And you\'re off exploring the world! The planet earth really is astonishing. It\'s difficult to imagine the vast distances between neighbouring continents, the wide oceans and seas that cover over 70% of its surface, and the near 200 countries that exist around the world. This 20cm diameter globe is a miniature model of our huge planet at your finger-tips, giving you access to continents, oceans, countries and major cities. It\'s a useful introduction to the fascinating science of geography, as well as being a convenient desktop reference.', 22.00, 0, 16, 'img_69bd5739b3ac33.55848488.webp', 1),
(17, 'Pokémon Mega Charizard X Ex Trading Card', 'pok-mon-mega-charizard-x-ex-trading-card', 'Under the right conditions - including a strong bond with its Trainer - Charizard can surpass its own limits and Mega Evolve! With this tin, you can choose the raging blue flames of Mega Charizard X ex, then find more Pokemon inside a handful of booster packs.', 22.00, 0, 15, 'img_69bd57c1b0b871.21861055.webp', 1),
(18, 'SIM Free Samsung Galaxy S26 Ultra 5G 512GB AI Phone Violet', 'sim-free-samsung-galaxy-s26-ultra-5g-512gb-ai-phone-violet', 'Meet Galaxy S26 Ultra. Featuring our most advanced display yet, dazzling camera innovation, and powerful Galaxy AI, it\'s here to help simplify your everyday. Your screen is personal by nature. And thanks to Privacy Display, it stays viewable only to you. Backed by the latest Qualcomm chipset built for Galaxy, it can perform deep searches and learn your habits with its Personal Data Engine. Galaxy S26 Ultra defines a new era of Galaxy AI phones – here to anticipate your every need and support you every step of the way. Just tap the Galaxy stars button to try out Galaxy AI\'s Photo Assist. Galaxy AI runs through every action – saving you time, keeping you connected and streamlining every day. And there\'s the all new Now Nudge, which gives content-aware suggestions related to you.', 1449.00, 1, 5, 'img_69bd589eb18a70.09464393.webp', 1),
(19, 'Petface House Scratcher', 'petface-house-scratcher', 'This Cat house scratcher gives your cat a place to have fun, exercise, explore, scratch and just relax. It features three different locations for them to relax including a house and a ladder.', 70.00, 14, 20, 'img_69bd632bc82e28.46608911.webp', 1),
(20, 'Cat Lounge and Play Scratcher', 'cat-lounge-and-play-scratcher', 'This cozy home they can call their own features a roomy condo, relaxing lounge basket, and two perfectly placed perches. Partially wrapped jute posts encourage satisfying cat-scratching sessions that can help maintain healthy nails. This cat tree is designed with overall stability in mind so you can feel good letting your fur friend climb, scratch, play, and nap to their heart\'s content.', 88.00, 6, 20, 'img_69bd63cfc64980.14864876.webp', 1),
(21, 'Ninja 7.6L Foodi Dual Zone Air Fryer and Dehydrator', 'ninja-7-6l-foodi-dual-zone-air-fryer-and-dehydrator', 'The air fryer that cooks 2 foods, 2 ways, and finishes at the same time. With 2 independent cooking zones, Sync to use different programs and times in each drawer - both finish cooking at the same time! Enjoy freshly cooked mains and sides together or cater to two different tastes. More people to feed? Match to make double the amount of food in the same amount of time! Uses little to no oil. 6 cooking functions. Cook from frozen. Extra-large 7.6L capacity. Dishwasher-safe parts.', 200.00, 22, 9, 'img_69cfa1d4e5f6c3.95570107.jpg', 1);

-- Seed Delivery Options
INSERT IGNORE INTO delivery_options (id, name, price, active, min_order_total) VALUES
(1, 'Standard Delivery', 3.99, 1, 0),
(2, 'Next Day Delivery', 6.99, 1, 0),
(3, 'Free Shipping (Over £50)', 0.00, 1, 50.00);
