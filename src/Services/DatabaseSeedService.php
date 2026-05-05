<?php

namespace App\Services;

use PDO;

class DatabaseSeedService implements DatabaseSeedServiceInterface {
    public function __construct(private PDO $db) {}

    public function seed(): void {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);

        if ($driver === 'mysql') {
            $sql = $this->getMySqlSeedSql($passwordHash);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $s) {
                if (!empty($s)) {
                    $this->db->exec($s);
                }
            }
        } else {
            $sql = $this->getSqliteSeedSql($passwordHash);
            $this->db->exec($sql);
        }
    }

    private function getMySqlSeedSql(string $passwordHash): string {
        return "
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
            (1, 'Admin', 'admin@shop.local', '{$passwordHash}', 'admin', 1),
            (2, 'Jane Smith', 'jane@example.com', '{$passwordHash}', 'customer', 1);

            INSERT IGNORE INTO attributes (id, name) VALUES (1, 'Brand'), (2, 'Color');
                
            INSERT IGNORE INTO attribute_values (id, attribute_id, value) VALUES 
            (1, 1, 'ProBook'), (2, 1, 'UltraPhone'), (3, 1, 'Studio'), (4, 1, 'Oxford'), 
            (5, 1, 'Merino'), (6, 1, 'Espresso'), (7, 1, 'GardenPro'), (8, 1, 'MiniBook'),
            (9, 2, 'Black'), (10, 2, 'Silver'), (11, 2, 'White'), (12, 2, 'Blue'), (13, 2, 'Red'), (14, 2, 'Green');

            INSERT IGNORE INTO product_attribute_values (product_id, attribute_value_id) VALUES (1, 1), (1, 10), (8, 8), (8, 11);
            INSERT IGNORE INTO product_attribute_values (product_id, attribute_value_id) VALUES (2, 2), (2, 9), (11, 2), (11, 10);
            INSERT IGNORE INTO product_attribute_values (product_id, attribute_value_id) VALUES (3, 3), (3, 9), (9, 3), (9, 12);
        ";
    }

    private function getSqliteSeedSql(string $passwordHash): string {
        return "
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
            (16,'Science Mad 20cm Illuminated Night Globe','science-mad-20cm-illuminated-night-globe','And you\'re off exploring the world!',22,0,16,'img_69bd5739b3ac33.55848488.webp',1,0,'2026-04-17 14:21:37',20,'DIS-GLOBE',0),
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
            (1, 'Admin', 'admin@shop.local', '{$passwordHash}', 'admin', 1),
            (2, 'Jane Smith', 'jane@example.com', '{$passwordHash}', 'customer', 1);

            INSERT OR IGNORE INTO attributes (id, name) VALUES (1, 'Brand'), (2, 'Color');
                
            INSERT OR IGNORE INTO attribute_values (id, attribute_id, value) VALUES 
            (1, 1, 'ProBook'), (2, 1, 'UltraPhone'), (3, 1, 'Studio'), (4, 1, 'Oxford'), 
            (5, 1, 'Merino'), (6, 1, 'Espresso'), (7, 1, 'GardenPro'), (8, 1, 'MiniBook'),
            (9, 2, 'Black'), (10, 2, 'Silver'), (11, 2, 'White'), (12, 2, 'Blue'), (13, 2, 'Red'), (14, 2, 'Green');

            INSERT OR IGNORE INTO product_attribute_values (product_id, attribute_value_id) VALUES (1, 1), (1, 10), (8, 8), (8, 11);
            INSERT OR IGNORE INTO product_attribute_values (product_id, attribute_value_id) VALUES (2, 2), (2, 9), (11, 2), (11, 10);
            INSERT OR IGNORE INTO product_attribute_values (product_id, attribute_value_id) VALUES (3, 3), (3, 9), (9, 3), (9, 12);
        ";
    }
}
