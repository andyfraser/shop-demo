<?php

namespace App\Services;

use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\DeliveryRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\AttributeRepositoryInterface;

class DatabaseSeedService implements DatabaseSeedServiceInterface {
    public function __construct(
        private CategoryRepositoryInterface $categoryRepo,
        private ProductRepositoryInterface $productRepo,
        private DeliveryRepositoryInterface $deliveryRepo,
        private UserRepositoryInterface $userRepo,
        private AttributeRepositoryInterface $attributeRepo
    ) {}

    public function seed(): void {
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);

        // 1. Categories
        $categories = [
            [1, 'Electronics', 'electronics', null, 'Gadgets, devices and tech', '💻'],
            [2, 'Clothing', 'clothing', null, 'Apparel for all occasions', '👕'],
            [3, 'Home & Garden', 'home-garden', null, 'For the home and garden', '🏠'],
            [4, 'Laptops', 'laptops', 1, 'Portable computers', '💻'],
            [5, 'Phones', 'phones', 1, 'Smartphones and accessories', '📱'],
            [6, 'Audio', 'audio', 1, 'Headphones, speakers and more', '🎧'],
            [7, 'Mens', 'mens', 2, 'Menswear', '👔'],
            [8, 'Womens', 'womens', 2, 'Womenswear', '👗'],
            [9, 'Kitchen', 'kitchen', 3, 'Kitchen appliances and tools', '🍳'],
            [10, 'Garden Tools', 'garden-tools', 3, 'Tools for the garden', '🌱'],
            [14, 'Gaming', 'gaming', 1, 'Gaming products.', '🎮'],
            [15, 'Toys', 'toys', null, 'Various toy categories.', '🧸'],
            [16, 'Discovery Toys', 'discovery-toys', 15, 'Learning & discovery toys.', '🧸'],
            [17, 'LEGO', 'lego', 16, 'All LEGO products.', '🧸'],
            [18, 'Pet', 'pet', null, 'Pet products.', '🐈'],
            [20, 'Cat', 'cat', 18, 'Cat products.', '🐈‍⬛'],
            [21, 'Dog', 'dog', 18, 'Dog products.', '🦮'],
            [22, 'Other Animals', 'other-animals', 18, 'Products for other pets.', '🐻'],
            [23, 'Books', 'books', null, 'Books.', '📚'],
            [24, 'Sports & Leisure', 'sports-leisure', null, 'Sports equipment and leisure paraphernalia.', '🏈'],
            [25, 'Sport', 'sport', 24, '', '⚾️'],
            [26, 'Leisure', 'leisure', 24, '', '♟️'],
        ];

        foreach ($categories as $c) {
            if (!$this->categoryRepo->findById($c[0])) {
                $this->categoryRepo->save([
                    'name' => $c[1],
                    'parent_id' => $c[3],
                    'description' => $c[4],
                    'icon' => $c[5]
                ], $c[0]);
            }
        }

        // 2. Products
        $products = [
            [1, 'ProBook Laptop 15"', 'probook-laptop-15', 'A powerful 15-inch laptop with 16GB RAM, 512GB SSD and a stunning display.', 899.99, 11, 4, 'img_69bd3779ba9bf9.94612358.jpg', 1, 0, 20, 'LAP-PROBK-15', 0],
            [2, 'UltraPhone X12', 'ultraphone-x12', 'The latest flagship smartphone.', 749, 20, 5, 'img_69bd376dc40e67.91131762.jpg', 1, 0, 20, 'PHN-UX12', 0],
            [3, 'Studio Wireless Headphones', 'studio-wireless-headphones', 'Premium over-ear headphones.', 299.95, 40, 6, 'img_69bd3762e90036.79611024.jpg', 1, 0, 20, 'AUD-STWIR', 0],
            [4, 'Classic Oxford Shirt', 'classic-oxford-shirt', 'Timeless Oxford weave cotton shirt.', 59.99, 79, 7, 'img_69bd3757beda69.95266902.jpg', 1, 0, 20, 'MEN-OXFRD', 1],
            [5, 'Merino Wool Jumper', 'merino-wool-jumper', 'Soft, lightweight and warm.', 89, 33, 8, 'img_69bd3728f36b86.03617973.jpg', 1, 0, 20, 'WOM-MERIN', 0],
            [6, 'Espresso Machine Pro', 'espresso-machine-pro', 'Barista-grade espresso at home.', 449, 11, 9, 'img_69bd371c0a7f91.86115081.jpg', 1, 0, 20, 'KIT-ESPRO', 0],
            [7, 'Carbon Steel Garden Trowel', 'carbon-steel-garden-trowel', 'Professional-grade carbon steel trowel.', 24.99, 59, 10, 'img_69bd370cad5f19.97444002.jpg', 1, 0, 20, 'GAR-TROWL', 0],
            [8, 'MiniBook Air 13"', 'minibook-air-13', 'Featherlight 13-inch ultrabook.', 1099, 7, 4, 'img_69bd36fea24797.63486949.jpg', 1, 1, 20, 'LAP-MINIB-13', 0],
            [9, 'Bluetooth Speaker Cube', 'bluetooth-speaker-cube', 'Compact, waterproof Bluetooth speaker.', 79.95, 54, 6, 'img_69bd3678dda058.99673075.jpg', 1, 1, 20, 'AUD-BTCUB', 0],
            [10, 'Cast Iron Skillet 28cm', 'cast-iron-skillet-28cm', 'Pre-seasoned cast iron skillet.', 39.99, 26, 9, 'img_69bd39df6196a1.38026816.jpg', 1, 0, 20, 'KIT-SKILT-28', 0],
            [11, 'iPhone 17 Pro Max', 'iphone-17-pro-max', 'iPhone 17 Pro Max. The most powerful iPhone ever.', 1199, 2, 5, 'img_69bd46751926a3.56450672.webp', 1, 1, 20, 'PHN-I17PM', 0],
            [12, 'Playstation 5', 'playstation-5', 'PlayStation 5 Console.', 479.99, 0, 14, 'img_69bd4d700341b0.13942450.webp', 1, 0, 20, 'GAM-PS5', 0],
            [13, 'Nintendo Switch 2 Console', 'nintendo-switch-2-console', 'The next evolution of the Nintendo Switch console is here!', 385.99, 28, 14, 'img_69bd4ec03e4005.93093090.webp', 1, 0, 20, 'GAM-NSW2', 0],
            [14, 'LEGO Speed Champions Mercedes-AMG F1 W15 Race Car Toy', 'lego-speed-champions-mercedes-amg-f1-w15-race-car-toy', 'F1 fans and kids aged 10 and up can enjoy exciting race action.', 23, 11, 17, 'img_69bd50dc58de60.49794891.webp', 1, 0, 20, 'LEG-MERC-F1', 0],
            [15, 'LEGO Star Wars BB-8 Astromech Droid', 'lego-star-wars-bb-8-astromech-droid', 'STAR WARS LEGO DROID FIGURE.', 80, 9, 17, 'img_69bd54828d6ee5.27048989.webp', 1, 0, 20, 'LEG-BB8', 0],
            [16, 'Science Mad 20cm Illuminated Night Globe', 'science-mad-20cm-illuminated-night-globe', 'And you\'re off exploring the world!', 22, 0, 16, 'img_69bd5739b3ac33.55848488.webp', 1, 0, 20, 'DIS-GLOBE', 0],
            [17, 'Pokémon Mega Charizard X Ex Trading Card', 'pok-mon-mega-charizard-x-ex-trading-card', 'Mega Evolve!', 22, 0, 15, 'img_69bd57c1b0b871.21861055.webp', 1, 0, 20, 'TOY-PKMN-CHAR', 0],
            [18, 'SIM Free Samsung Galaxy S26 Ultra 5G 512GB AI Phone Violet', 'sim-free-samsung-galaxy-s26-ultra-5g-512gb-ai-phone-violet', 'Meet Galaxy S26 Ultra.', 1449, 1, 5, 'img_69bd589eb18a70.09464393.webp', 1, 0, 20, 'PHN-S26U-512', 0],
            [19, 'Petface House Scratcher', 'petface-house-scratcher', 'This Cat house scratcher.', 70, 13, 20, 'img_69bd632bc82e28.46608911.webp', 1, 0, 20, 'CAT-HOUSE', 0],
            [20, 'Cat Lounge and Play Scratcher', 'cat-lounge-and-play-scratcher', 'This cozy home.', 88, 6, 20, 'img_69bd63cfc64980.14864876.webp', 1, 0, 20, 'CAT-LOUNG', 0],
            [21, 'Ninja 7.6L Foodi Dual Zone Air Fryer and Dehydrator', 'ninja-7-6l-foodi-dual-zone-air-fryer-and-dehydrator', 'The air fryer.', 200, 21, 9, 'img_69cfa1d4e5f6c3.95570107.jpg', 1, 0, 20, 'KIT-NINJA-AF', 0],
            [22, 'McGregor 23cm Cordless Grass Trimmer - 18V', 'mcgregor-23cm-cordless-grass-trimmer-18v', 'This McGregor 18V 23cm grass trimmer.', 36, 12, 10, 'img_69d25436496576.37754497.jpg', 1, 0, 20, 'GAR-MCGR-TRIM', 0],
            [23, 'The Book', 'the-book', 'Hand-illustrated guide.', 99, 13, 23, 'img_69e8d00f114974.93305311.webp', 1, 1, 0, 'BK-BOOK', 0],
        ];

        foreach ($products as $p) {
            if (!$this->productRepo->findById($p[0])) {
                $this->productRepo->save([
                    'name' => $p[1],
                    'description' => $p[3],
                    'price' => $p[4],
                    'stock' => $p[5],
                    'category_id' => $p[6],
                    'image' => $p[7],
                    'active' => $p[8],
                    'featured' => $p[9],
                    'vat_rate' => $p[10],
                    'sku' => $p[11],
                    'force_variant' => $p[12]
                ], $p[0]);
            }
        }

        // 3. Variants
        $variants = [
            [1, 4, 'Small', 'MEN-OXFRD-SMALL', null, 12, 1],
            [2, 4, 'Medium', 'MEN-OXFRD-MEDIUM', null, 20, 1],
            [3, 4, 'Large', 'MEN-OXFRD-LARGE', 64.99, 14, 1],
            [4, 4, 'Extra Large', 'MEN-OXFRD-XLARGE', 64.99, 0, 1],
            [5, 9, 'Blue', 'AUD-BTCUB-BLUE', null, 4, 1],
        ];

        foreach ($variants as $v) {
            if (!$this->productRepo->findVariantById($v[0])) {
                $this->productRepo->saveVariant([
                    'product_id' => $v[1],
                    'name' => $v[2],
                    'sku' => $v[3],
                    'price' => $v[4],
                    'stock' => $v[5],
                    'active' => $v[6],
                    'sort_order' => 0
                ], $v[0]);
            }
        }

        // 4. Delivery Options
        $delivery = [
            [1, 'Standard Delivery', 3.99, 1, 0],
            [2, 'Next Day Delivery', 6.99, 1, 0],
            [3, 'Free Shipping (Over £50)', 0, 1, 50],
        ];

        foreach ($delivery as $d) {
            if (!$this->deliveryRepo->findById($d[0])) {
                $this->deliveryRepo->save([
                    'name' => $d[1],
                    'price' => $d[2],
                    'active' => $d[3],
                    'min_order_total' => $d[4]
                ], $d[0]);
            }
        }

        // 5. Users
        $users = [
            [1, 'Admin', 'admin@shop.local', $passwordHash, 'admin', 1],
            [2, 'Jane Smith', 'jane@example.com', $passwordHash, 'customer', 1],
        ];

        foreach ($users as $u) {
            if (!$this->userRepo->findById($u[0])) {
                $this->userRepo->save([
                    'name' => $u[1],
                    'email' => $u[2],
                    'password_hash' => $u[3],
                    'role' => $u[4],
                    'is_verified' => $u[5],
                    'verification_token' => null,
                    'address' => null
                ], $u[0]);
            }
        }

        // 6. Attributes
        $attributes = [
            [1, 'Brand'],
            [2, 'Color'],
        ];

        foreach ($attributes as $a) {
            if (!$this->attributeRepo->findById($a[0])) {
                $this->attributeRepo->save(['name' => $a[1]], $a[0]);
            }
        }

        // 7. Attribute Values
        $values = [
            [1, 1, 'ProBook'], [2, 1, 'UltraPhone'], [3, 1, 'Studio'], [4, 1, 'Oxford'],
            [5, 1, 'Merino'], [6, 1, 'Espresso'], [7, 1, 'GardenPro'], [8, 1, 'MiniBook'],
            [9, 2, 'Black'], [10, 2, 'Silver'], [11, 2, 'White'], [12, 2, 'Blue'], [13, 2, 'Red'], [14, 2, 'Green'],
        ];

        foreach ($values as $v) {
            // No easy way to find by ID and check, let's just use raw SQL for this mapping table or add findById to AttributeValue
            // Actually, I can just use getValues and check
            $existing = $this->attributeRepo->getValues($v[1]);
            $found = false;
            foreach ($existing as $ev) {
                if ($ev->id == $v[0]) { $found = true; break; }
            }
            if (!$found) {
                $this->attributeRepo->saveValue([
                    'attribute_id' => $v[1],
                    'value' => $v[2],
                    'sort_order' => 0
                ], $v[0]);
            }
        }

        // 8. Product Attribute Mappings
        $mappings = [
            [1, 1], [1, 10], [8, 8], [8, 11],
            [2, 2], [2, 9], [11, 2], [11, 10],
            [3, 3], [3, 9], [9, 3], [9, 12],
        ];

        foreach ($mappings as $m) {
            $existing = $this->attributeRepo->getProductAttributeValues($m[0]);
            if (!in_array($m[1], $existing)) {
                $existing[] = $m[1];
                $this->attributeRepo->saveProductAttributeValues($m[0], $existing);
            }
        }
    }
}
