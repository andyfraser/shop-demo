-- Shop Demo Backup
-- Generated: 2026-05-04 14:41:50

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `attribute_values`;
CREATE TABLE `attribute_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribute_id` int NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_attr_val_attr` (`attribute_id`),
  CONSTRAINT `attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `attribute_values` VALUES 
('1', '1', 'ProBook', '2026-04-28 08:07:46', '7'),
('2', '1', 'UltraPhone', '2026-04-28 08:07:46', '9'),
('3', '1', 'Studio', '2026-04-28 08:07:46', '8'),
('4', '1', 'Oxford', '2026-04-28 08:07:46', '6'),
('5', '1', 'Merino', '2026-04-28 08:07:46', '4'),
('6', '1', 'Espresso', '2026-04-28 08:07:46', '2'),
('7', '1', 'GardenPro', '2026-04-28 08:07:46', '3'),
('8', '1', 'MiniBook', '2026-04-28 08:07:46', '5'),
('9', '2', 'Black', '2026-04-28 08:07:46', '0'),
('10', '2', 'Silver', '2026-04-28 08:07:46', '0'),
('11', '2', 'White', '2026-04-28 08:07:46', '0'),
('12', '2', 'Blue', '2026-04-28 08:07:46', '0'),
('13', '2', 'Red', '2026-04-28 08:07:46', '0'),
('14', '2', 'Green', '2026-04-28 08:07:46', '0'),
('19', '3', 'Extra small', '2026-04-28 10:47:16', '0'),
('20', '3', 'Small', '2026-04-28 10:47:16', '1'),
('21', '3', 'Medium', '2026-04-28 10:47:16', '2'),
('22', '3', 'Large', '2026-04-28 10:47:16', '3'),
('23', '3', 'Extra large', '2026-04-28 10:47:16', '4'),
('24', '3', '2XL', '2026-04-28 10:47:16', '5'),
('25', '1', 'Ninja', '2026-04-29 12:18:56', '10'),
('26', '1', 'Nintendo', '2026-04-29 12:20:36', '1'),
('27', '1', 'Sony', '2026-04-29 12:20:36', '0');

DROP TABLE IF EXISTS `attributes`;
CREATE TABLE `attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `attributes` VALUES 
('1', 'Brand', '2026-04-28 08:07:46'),
('2', 'Color', '2026-04-28 08:07:46'),
('3', 'Size', '2026-04-28 10:34:48');

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `cart_items` VALUES 
('6', '1', '24', '14', '1', '2026-05-04 15:37:01');

DROP TABLE IF EXISTS `carts`;
CREATE TABLE `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  `recovery_email_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `carts` VALUES 
('1', '1', '7f8db179108c7c6b85a449f308043684', '2026-05-04 15:37:01', '2026-05-02 11:31:00');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `parent_id` int DEFAULT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `categories` VALUES 
('1', 'Electronics', 'electronics', NULL, 'Gadgets, devices and tech', '💻', '2026-04-17 14:21:37'),
('2', 'Clothing', 'clothing', NULL, 'Apparel for all occasions', '👕', '2026-04-17 14:21:37'),
('3', 'Home & Garden', 'home-garden', NULL, 'For the home and garden', '🏠', '2026-04-17 14:21:37'),
('4', 'Laptops', 'laptops', '1', 'Portable computers', '💻', '2026-04-17 14:21:37'),
('5', 'Phones', 'phones', '1', 'Smartphones and accessories', '📱', '2026-04-17 14:21:37'),
('6', 'Audio', 'audio', '1', 'Headphones, speakers and more', '🎧', '2026-04-17 14:21:37'),
('7', 'Mens', 'mens', '2', 'Menswear', '👔', '2026-04-17 14:21:37'),
('8', 'Womens', 'womens', '2', 'Womenswear', '👗', '2026-04-17 14:21:37'),
('9', 'Kitchen', 'kitchen', '3', 'Kitchen appliances and tools', '🍳', '2026-04-17 14:21:37'),
('10', 'Garden Tools', 'garden-tools', '3', 'Tools for the garden', '🌱', '2026-04-17 14:21:37'),
('14', 'Gaming', 'gaming', '1', 'Gaming products.', '🎮', '2026-04-17 14:21:37'),
('15', 'Toys', 'toys', NULL, 'Various toy categories.', '🧸', '2026-04-17 14:21:37'),
('16', 'Discovery Toys', 'discovery-toys', '15', 'Learning & discovery toys.', '🧸', '2026-04-17 14:21:37'),
('17', 'LEGO', 'lego', '16', 'All LEGO products.', '🧸', '2026-04-17 14:21:37'),
('18', 'Pet', 'pet', NULL, 'Pet products.', '🐈', '2026-04-17 14:21:37'),
('20', 'Cat', 'cat', '18', 'Cat products.', '🐈‍⬛', '2026-04-17 14:21:37'),
('21', 'Dog', 'dog', '18', 'Dog products.', '🦮', '2026-04-17 14:21:37'),
('22', 'Other Animals', 'other-animals', '18', 'Products for other pets.', '🐻', '2026-04-17 14:21:37'),
('23', 'Books', 'books', NULL, 'Books.', '📚', '2026-04-22 14:42:00'),
('24', 'Sports & Leisure', 'sports-leisure', NULL, 'Sports equipment and leisure paraphernalia.', '🏈', '2026-04-22 14:51:51'),
('25', 'Sport', 'sport', '24', '', '⚾️', '2026-04-22 14:52:29'),
('26', 'Leisure', 'leisure', '24', '', '♟️', '2026-04-22 14:52:46');

DROP TABLE IF EXISTS `delivery_options`;
CREATE TABLE `delivery_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` double NOT NULL,
  `active` tinyint(1) DEFAULT '1',
  `min_order_total` double DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `delivery_options` VALUES 
('1', 'Standard Delivery', '3.99', '1', '0'),
('2', 'Next Day Delivery', '6.99', '1', '0'),
('3', 'Free Shipping (Over £50)', '0', '1', '50'),
('4', 'Special Delivery (before 9am next day)', '10.99', '1', '0');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `migrations` VALUES 
('1', 'm0001_initial_schema', '2026-04-27 14:12:50'),
('2', 'm0002_add_variant_sort_order', '2026-04-27 16:02:33'),
('3', 'm0003_add_product_attributes', '2026-04-28 08:07:46'),
('4', 'm0004_add_attribute_value_sort_order', '2026-04-28 10:48:17'),
('5', 'm0005_product_variant_attributes', '2026-04-28 11:09:37'),
('6', 'm0006_add_payment_fields', '2026-04-28 13:57:49'),
('7', 'm0007_refunds_and_returns', '2026-04-28 14:09:19'),
('8', 'm0008_add_delivery_refunded_to_orders', '2026-04-28 15:01:27'),
('9', 'm0009_add_order_status_history', '2026-04-28 15:45:59'),
('10', 'm0010_add_wishlists_table', '2026-04-28 17:40:43'),
('11', 'm0011_product_reviews', '2026-04-29 11:38:47'),
('12', 'm0012_user_addresses', '2026-04-29 11:38:47'),
('13', 'm0013_persistent_carts', '2026-04-29 11:38:47'),
('14', 'm0014_add_label_to_user_addresses', '2026-04-29 13:12:15'),
('16', 'm0015_create_scheduled_tasks', '2026-05-01 14:29:47');

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` double NOT NULL,
  `vat_rate` double DEFAULT '0',
  `vat_amount` double DEFAULT '0',
  `variant_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `order_items` VALUES 
('1', '1', '3', '1', '299.95', '0', '0', NULL),
('2', '2', '22', '1', '36', '0', '0', NULL),
('3', '3', '22', '1', '36', '0', '0', NULL),
('4', '4', '2', '1', '749', '20', '124.83333333333', NULL),
('5', '5', '23', '1', '99', '0', '0', NULL),
('6', '6', '3', '1', '299.95', '20', '49.991666666667', NULL),
('7', '7', '23', '1', '99', '0', '0', NULL),
('8', '8', '10', '1', '39.99', '20', '6.665', NULL),
('9', '9', '5', '1', '89', '20', '14.833333333333', NULL),
('10', '10', '10', '1', '39.99', '20', '6.665', NULL),
('11', '10', '21', '1', '200', '20', '33.333333333333', NULL),
('12', '11', '2', '1', '749', '20', '124.83333333333', NULL),
('13', '12', '6', '1', '449', '20', '74.833333333333', NULL),
('14', '12', '19', '1', '70', '20', '11.666666666667', NULL),
('15', '13', '4', '1', '59.99', '20', '9.9983333333333', '1'),
('16', '14', '4', '1', '59.99', '20', '9.9983333333333', '1'),
('17', '15', '4', '1', '59.99', '20', '9.9983333333333', '1'),
('18', '16', '4', '1', '64.99', '20', '10.831666666667', '3'),
('19', '17', '9', '1', '79.95', '20', '13.325', NULL),
('20', '17', '9', '1', '79.95', '20', '13.325', '5'),
('21', '18', '9', '1', '79.95', '20', '13.325', NULL),
('22', '18', '5', '1', '89', '20', '14.833333333333', '8'),
('23', '19', '9', '1', '79.95', '20', '13.325', NULL),
('24', '20', '13', '1', '385.99', '20', '64.331666666667', NULL),
('25', '21', '5', '1', '89', '20', '14.833333333333', '8'),
('26', '22', '8', '1', '1099', '20', '183.16666666667', NULL),
('27', '23', '3', '1', '299.95', '20', '49.991666666667', NULL),
('28', '24', '3', '1', '299.95', '20', '49.991666666667', NULL),
('29', '24', '22', '1', '36', '20', '6', NULL),
('30', '25', '7', '1', '24.99', '20', '4.165', NULL),
('31', '25', '14', '1', '23', '20', '3.8333333333333', NULL),
('32', '26', '9', '1', '79.95', '20', '13.325', NULL),
('33', '27', '23', '1', '99', '0', '0', NULL),
('34', '28', '1', '1', '899.99', '20', '149.99833333333', NULL),
('35', '29', '21', '1', '200', '20', '33.333333333333', NULL),
('36', '30', '3', '1', '299.95', '20', '49.991666666667', NULL),
('37', '31', '9', '1', '79.95', '20', '13.325', NULL),
('38', '32', '22', '1', '36', '20', '6', NULL);

DROP TABLE IF EXISTS `order_status_history`;
CREATE TABLE `order_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text,
  `created_by_user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `order_status_history` VALUES 
('1', '25', 'pending', 'Order created', NULL, '2026-04-28 15:48:57'),
('2', '25', 'confirmed', '', NULL, '2026-04-28 15:48:57'),
('3', '25', 'shipped', '', '1', '2026-04-28 15:53:03'),
('4', '25', 'delivered', '', '1', '2026-04-28 15:53:25'),
('5', '25', 'returning', 'Return requested: Don\'t want it.', '1', '2026-04-28 16:08:25'),
('6', '25', 'fully refunded', 'Return #12 approved. Refunded £26.99', '1', '2026-04-28 16:08:35'),
('7', '26', 'pending', 'Order created', NULL, '2026-04-28 16:09:12'),
('8', '26', 'confirmed', '', NULL, '2026-04-28 16:09:12'),
('9', '26', 'shipped', '', '1', '2026-04-28 16:09:19'),
('10', '26', 'delivered', '', '1', '2026-04-28 16:09:22'),
('11', '26', 'returning', 'Return requested: Doens\'t work.', '1', '2026-04-28 16:09:47'),
('12', '26', 'refunded', 'Return #13 approved. Refunded £79.95', '1', '2026-04-28 16:09:57'),
('13', '27', 'pending', 'Order created', NULL, '2026-04-28 16:18:06'),
('14', '27', 'confirmed', '', NULL, '2026-04-28 16:18:06'),
('15', '28', 'pending', 'Order created', '1', '2026-04-28 17:01:33'),
('16', '28', 'confirmed', 'Paid via TEST-69f0d9ddda230', '1', '2026-04-28 17:01:33'),
('17', '29', 'pending', 'Order created', '2', '2026-04-28 17:02:21'),
('18', '29', 'confirmed', 'Paid via TEST-69f0da0d0ed20', '2', '2026-04-28 17:02:21'),
('19', '29', 'shipped', '', '1', '2026-04-28 17:02:57'),
('20', '29', 'delivered', '', '1', '2026-04-28 17:03:11'),
('21', '29', 'returning', 'Return requested: Don\'t want it.', '2', '2026-04-28 17:03:31'),
('22', '29', 'refunded', 'Return #14 approved. Refunded £200.00', '1', '2026-04-28 17:03:44'),
('23', '30', 'pending', 'Order created', '1', '2026-04-29 14:51:16'),
('24', '30', 'confirmed', 'Paid via TEST-69f20cd42999c', '1', '2026-04-29 14:51:16'),
('25', '31', 'pending', 'Order created', '1', '2026-04-29 15:53:41'),
('26', '31', 'confirmed', 'Paid via TEST-69f21b75c336c', '1', '2026-04-29 15:53:41'),
('27', '32', 'pending', 'Order created', '1', '2026-05-02 12:35:32'),
('28', '32', 'confirmed', 'Paid via TEST-69f5e184bf341', '1', '2026-05-02 12:35:32');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `total` double NOT NULL,
  `shipping_address` text,
  `notes` text,
  `delivery_method` varchar(255) DEFAULT NULL,
  `delivery_cost` double DEFAULT '0',
  `delivery_refunded` tinyint(1) DEFAULT '0',
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_vat_amount` double DEFAULT '0',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_transaction_id` varchar(255) DEFAULT NULL,
  `refund_status` varchar(20) DEFAULT NULL,
  `refunded_amount` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_orders_user_created` (`user_id`,`created_at`),
  KEY `idx_orders_status_created` (`status`,`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `orders` VALUES 
('1', '1', 'delivered', '306.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '0', 'admin@shop.local', 'Admin', '2026-04-18 09:59:47', '0', NULL, 'pending', NULL, NULL, '0.00'),
('2', '1', 'confirmed', '39.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-18 13:27:43', '0', NULL, 'pending', NULL, NULL, '0.00'),
('3', '1', 'cancelled', '42.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '0', 'admin@shop.local', 'Admin', '2026-04-21 12:14:04', '0', NULL, 'pending', NULL, NULL, '0.00'),
('4', '1', 'shipped', '752.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-22 14:35:03', '125.49833333333', NULL, 'pending', NULL, NULL, '0.00'),
('5', '1', 'shipped', '102.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-22 14:42:49', '0.665', NULL, 'pending', NULL, NULL, '0.00'),
('6', '1', 'shipped', '303.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-25 10:49:11', '50.656666666667', NULL, 'pending', NULL, NULL, '0.00'),
('7', '1', 'shipped', '99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Free Shipping (Over £50)', '0', '0', 'admin@shop.local', 'Admin', '2026-04-25 12:54:27', '0', NULL, 'pending', NULL, NULL, '0.00'),
('8', '1', 'confirmed', '43.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-26 11:26:26', '7.33', NULL, 'pending', NULL, NULL, '0.00'),
('9', '1', 'cancelled', '89', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Free Shipping (Over £50)', '0', '0', 'admin@shop.local', 'Admin', '2026-04-26 13:32:24', '14.833333333333', NULL, 'pending', NULL, NULL, '0.00'),
('10', '1', 'confirmed', '246.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '0', 'admin@shop.local', 'Admin', '2026-04-26 15:15:20', '41.163333333333', NULL, 'pending', NULL, NULL, '0.00'),
('11', '1', 'pending', '759.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Special Delivery (before 9am next day)', '10.99', '0', 'admin@shop.local', 'Admin', '2026-04-26 16:26:08', '126.665', NULL, 'pending', NULL, NULL, '0.00'),
('12', '2', 'shipped', '525.99', '2 Test St\r\nTest\r\nTS2 2ST', '', 'Next Day Delivery', '6.99', '0', 'jane@example.com', 'Jane Smith', '2026-04-27 12:12:14', '87.665', NULL, 'pending', NULL, NULL, '0.00'),
('13', '1', 'pending', '63.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-27 13:49:48', '10.663333333333', NULL, 'pending', NULL, NULL, '0.00'),
('14', '1', 'delivered', '63.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-27 13:55:45', '10.663333333333', NULL, 'pending', NULL, NULL, '0.00'),
('15', '1', 'delivered', '63.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-27 13:57:34', '10.663333333333', NULL, 'pending', NULL, NULL, '0.00'),
('16', '1', 'delivered', '68.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-27 14:01:56', '11.496666666667', NULL, 'pending', NULL, NULL, '0.00'),
('17', '1', 'cancelled', '166.89', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '0', 'admin@shop.local', 'Admin', '2026-04-27 14:05:22', '27.815', NULL, 'pending', NULL, NULL, '0.00'),
('18', '1', 'delivered', '175.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 13:59:34', '29.323333333333', 'manual', 'paid', 'TEST-69f0af36576fe', 'partially_refunded', '79.95'),
('19', '1', 'cancelled', '83.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 14:28:24', '13.99', 'manual', 'paid', 'TEST-69f0b5f828e86', 'refunded', '83.94'),
('20', '1', 'cancelled', '389.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 14:31:36', '64.996666666667', 'manual', 'paid', 'TEST-69f0b6b8419e1', 'refunded', '389.98'),
('21', '1', 'delivered', '92.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 14:32:48', '15.498333333333', 'manual', 'paid', 'TEST-69f0b700318ac', 'partially_refunded', '89.00'),
('22', '1', 'delivered', '1102.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 14:53:37', '183.83166666667', 'manual', 'paid', 'TEST-69f0bbe118637', 'partially_refunded', '1099.00'),
('23', '1', 'fully refunded', '303.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '1', 'admin@shop.local', 'Admin', '2026-04-28 15:03:44', '50.656666666667', 'manual', 'paid', 'TEST-69f0be40ef8e3', 'fully_refunded', '303.94'),
('24', '1', 'not refunded', '342.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '1', 'admin@shop.local', 'Admin', '2026-04-28 15:07:25', '57.156666666667', 'manual', 'paid', 'TEST-69f0bf1d9283e', 'partially_refunded', '42.99'),
('25', '1', 'fully refunded', '51.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '1', 'admin@shop.local', 'Admin', '2026-04-28 15:48:57', '8.6633333333333', 'manual', 'paid', 'TEST-69f0c8d9dce1f', 'fully_refunded', '51.98'),
('26', '1', 'refunded', '86.94', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Next Day Delivery', '6.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 16:09:12', '14.49', 'manual', 'paid', 'TEST-69f0cd986320b', 'partially_refunded', '79.95'),
('27', '1', 'confirmed', '102.99', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 16:18:06', '0.665', 'manual', 'paid', 'TEST-69f0cfae9565d', NULL, '0.00'),
('28', '1', 'confirmed', '903.98', '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-28 17:01:33', '150.66333333333', 'manual', 'paid', 'TEST-69f0d9ddda230', NULL, '0.00'),
('29', '2', 'refunded', '203.99', '2 Test St\r\nTest\r\nTS2 2ST', '', 'Standard Delivery', '3.99', '0', 'jane@example.com', 'Jane Smith', '2026-04-28 17:02:21', '33.998333333333', 'manual', 'paid', 'TEST-69f0da0d0ed20', 'partially_refunded', '200.00'),
('30', '1', 'confirmed', '303.94', '1 Test St\nTest\nTS1 1ST\nUnited Kingdom', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-29 14:51:16', '50.656666666667', 'manual', 'paid', 'TEST-69f20cd42999c', NULL, '0.00'),
('31', '1', 'confirmed', '83.94', '1 Test St\nTest\nTS1 1ST\nUnited Kingdom', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-04-29 15:53:41', '13.99', 'manual', 'paid', 'TEST-69f21b75c336c', NULL, '0.00'),
('32', '1', 'confirmed', '39.99', '1 Test St\nTest\nTS1 1ST\nUnited Kingdom', '', 'Standard Delivery', '3.99', '0', 'admin@shop.local', 'Admin', '2026-05-02 12:35:32', '6.665', 'manual', 'paid', 'TEST-69f5e184bf341', NULL, '0.00');

DROP TABLE IF EXISTS `product_attribute_values`;
CREATE TABLE `product_attribute_values` (
  `product_id` int NOT NULL,
  `attribute_value_id` int NOT NULL,
  PRIMARY KEY (`product_id`,`attribute_value_id`),
  KEY `idx_prod_attr_val_val` (`attribute_value_id`),
  CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `product_attribute_values` VALUES 
('1', '1'),
('2', '2'),
('11', '2'),
('3', '3'),
('9', '3'),
('6', '6'),
('8', '8'),
('2', '9'),
('3', '9'),
('9', '9'),
('1', '10'),
('11', '10'),
('8', '11'),
('9', '12'),
('24', '12'),
('24', '14'),
('4', '19'),
('5', '19'),
('4', '20'),
('5', '20'),
('4', '21'),
('5', '21'),
('4', '22'),
('5', '22'),
('24', '22'),
('4', '23'),
('5', '23'),
('24', '23'),
('4', '24'),
('5', '24'),
('21', '25'),
('13', '26'),
('12', '27');

DROP TABLE IF EXISTS `product_variant_attribute_values`;
CREATE TABLE `product_variant_attribute_values` (
  `variant_id` int NOT NULL,
  `attribute_value_id` int NOT NULL,
  PRIMARY KEY (`variant_id`,`attribute_value_id`),
  KEY `attribute_value_id` (`attribute_value_id`),
  CONSTRAINT `product_variant_attribute_values_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_attribute_values_ibfk_2` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `product_variant_attribute_values` VALUES 
('11', '12'),
('12', '12'),
('13', '14'),
('14', '14'),
('6', '19'),
('1', '20'),
('7', '20'),
('2', '21'),
('8', '21'),
('3', '22'),
('9', '22'),
('11', '22'),
('13', '22'),
('4', '23'),
('10', '23'),
('12', '23'),
('14', '23');

DROP TABLE IF EXISTS `product_variant_attributes`;
CREATE TABLE `product_variant_attributes` (
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  PRIMARY KEY (`product_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `product_variant_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `product_variant_attributes` VALUES 
('24', '2'),
('4', '3'),
('5', '3'),
('24', '3');

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `stock` int DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `product_variants` VALUES 
('1', '4', 'Small', 'MEN-OXFRD-SMALL', NULL, '13', '1', '0', '2026-04-27 13:48:16'),
('2', '4', 'Medium', 'MEN-OXFRD-MEDIUM', NULL, '20', '1', '1', '2026-04-27 14:00:38'),
('3', '4', 'Large', 'MEN-OXFRD-LARGE', '64.99', '17', '1', '2', '2026-04-27 14:00:38'),
('4', '4', 'Extra Large', 'MEN-OXFRD-XLARGE', '64.99', '0', '1', '3', '2026-04-27 14:01:11'),
('5', '9', 'Blue', 'AUD-BTCUB-BLUE', NULL, '15', '1', '0', '2026-04-27 14:04:49'),
('6', '5', 'Extra small', 'WOM-MERIN-XSMALL', NULL, '20', '1', '0', '2026-04-28 11:19:16'),
('7', '5', 'Small', 'WOM-MERIN-SMALL', NULL, '0', '1', '1', '2026-04-28 11:22:37'),
('8', '5', 'Medium', 'WOM-MERIN-MEDIUM', NULL, '1', '1', '2', '2026-04-28 11:22:37'),
('9', '5', 'Large', 'WOM-MERIN-LARGE', NULL, '20', '1', '3', '2026-04-28 11:22:37'),
('10', '5', 'Extra large', 'WOM-MERIN-XLARGE', NULL, '20', '1', '4', '2026-04-28 11:22:37'),
('11', '24', 'Large Blue', 'MENS-REG-SHIRT-LB', NULL, '20', '1', '0', '2026-05-04 14:05:32'),
('12', '24', 'XL Blue', 'MENS-REG-SHIRT-XLB', '25', '15', '1', '1', '2026-05-04 14:05:32'),
('13', '24', 'Large Green', 'MENS-REG-SHIRT-LG', NULL, '30', '1', '2', '2026-05-04 14:05:32'),
('14', '24', 'XL Green', 'MENS-REG-SHIRT-XLG', '25', '25', '1', '3', '2026-05-04 14:05:32');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `price` double NOT NULL,
  `stock` int DEFAULT '0',
  `category_id` int DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `featured` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `vat_rate` double DEFAULT '20',
  `sku` varchar(255) DEFAULT NULL,
  `force_variant` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_products_category_active` (`category_id`,`active`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `products` VALUES 
('1', 'ProBook Laptop 15\"', 'probook-laptop-15', 'A powerful 15-inch laptop with 16GB RAM, 512GB SSD and a stunning display. Perfect for work and creativity.', '899.99', '10', '4', 'img_69bd3779ba9bf9.94612358.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'LAP-PROBK-15', '0'),
('2', 'UltraPhone X12', 'ultraphone-x12', 'The latest flagship smartphone featuring a triple-camera system, 5G connectivity and all-day battery life.', '749', '20', '5', 'img_69bd376dc40e67.91131762.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'PHN-UX12', '0'),
('3', 'Studio Wireless Headphones', 'studio-wireless-headphones', 'Premium over-ear headphones with active noise cancellation and 30-hour battery. Studio-quality sound anywhere.', '299.95', '38', '6', 'img_69bd3762e90036.79611024.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'AUD-STWIR', '0'),
('4', 'Classic Oxford Shirt', 'classic-oxford-shirt', 'Timeless Oxford weave cotton shirt. Versatile enough for the office or weekend. Available in multiple colours.', '59.99', '79', '7', 'img_69bd3757beda69.95266902.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'MEN-OXFRD', '1'),
('5', 'Merino Wool Jumper', 'merino-wool-jumper', 'Soft, lightweight and warm. This merino wool jumper is a wardrobe essential for the cooler months.', '89', '33', '8', 'img_69bd3728f36b86.03617973.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'WOM-MERIN', '1'),
('6', 'Espresso Machine Pro', 'espresso-machine-pro', 'Barista-grade espresso at home. 15-bar pump pressure, built-in grinder, and milk frother included.', '449', '11', '9', 'img_69bd371c0a7f91.86115081.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'KIT-ESPRO', '0'),
('7', 'Carbon Steel Garden Trowel', 'carbon-steel-garden-trowel', 'Professional-grade carbon steel trowel with an ergonomic hardwood handle. Built to last a lifetime.', '24.99', '59', '10', 'img_69bd370cad5f19.97444002.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'GAR-TROWL', '0'),
('8', 'MiniBook Air 13\"', 'minibook-air-13', 'Featherlight 13-inch ultrabook. All-day battery, fanless design, and a gorgeous Retina-class display.', '1099', '7', '4', 'img_69bd36fea24797.63486949.jpg', '1', '1', '2026-04-17 14:21:37', '20', 'LAP-MINIB-13', '0'),
('9', 'Bluetooth Speaker Cube', 'bluetooth-speaker-cube', 'Compact, waterproof Bluetooth speaker delivering surprisingly big sound. Perfect for outdoors.', '79.95', '52', '6', 'img_69bd3678dda058.99673075.jpg', '1', '1', '2026-04-17 14:21:37', '20', 'AUD-BTCUB', '0'),
('10', 'Cast Iron Skillet 28cm', 'cast-iron-skillet-28cm', 'Pre-seasoned cast iron skillet. Sears, bakes, grills and fries. Virtually indestructible.', '39.99', '26', '9', 'img_69bd39df6196a1.38026816.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'KIT-SKILT-28', '0'),
('11', 'iPhone 17 Pro Max', 'iphone-17-pro-max', 'iPhone 17 Pro Max. The most powerful iPhone ever. Brilliant 6.9-inch display, aluminium unibody design, A19 Pro chip, all 48MP rear cameras and best-ever battery life. UNIBODY DESIGN. FOR EXCEPTIONAL POWER - Heat-forged aluminium unibody design for the most powerful iPhone ever made. DURABLE CERAMIC SHIELD. FRONT AND BACK - Ceramic Shield protects the back of iPhone 17 Pro Max, making it 4x more resistant to cracks. And the new Ceramic Shield 2 on the front has 3x better scratch resistance.', '1199', '2', '5', 'img_69bd46751926a3.56450672.webp', '1', '1', '2026-04-17 14:21:37', '20', 'PHN-I17PM', '0'),
('12', 'Playstation 5', 'playstation-5', 'PlayStation 5 Console. The PS5 console unleashes new gaming possibilities that you never anticipated. Experience lightning-fast loading with an ultra-high speed SSD, deeper immersion with support for haptic feedback, adaptive triggers, and 3D Audio, and an all-new generation of incredible PlayStation games.', '479.99', '0', '14', 'img_69bd4d700341b0.13942450.webp', '1', '0', '2026-04-17 14:21:37', '20', 'GAM-PS5', '0'),
('13', 'Nintendo Switch 2 Console', 'nintendo-switch-2-console', 'The next evolution of the Nintendo Switch console is here!\r\n\r\nBring games to life with a larger 1080p screen—or connect to a TV and play in up to 4K resolution. Support for HDR, VRR, and frame rates up to 120 fps lets you enjoy vivid colour, clarity, and smooth gameplay. Snap the new Joy-Con 2 controllers into place with magnetic connectors. Each controller can even be used as a mouse in compatible games.', '385.99', '28', '14', 'img_69bd4ec03e4005.93093090.webp', '1', '0', '2026-04-17 14:21:37', '20', 'GAM-NSW2', '0'),
('14', 'LEGO Speed Champions Mercedes-AMG F1 W15 Race Car Toy', 'lego-speed-champions-mercedes-amg-f1-w15-race-car-toy', 'F1 fans and kids aged 10 and up can enjoy exciting race action with this LEGO Speed Champions Mercedes-AMG PETRONAS F1 Team toy car.', '23', '11', '17', 'img_69bd50dc58de60.49794891.webp', '1', '0', '2026-04-17 14:21:37', '20', 'LEG-MERC-F1', '0'),
('15', 'LEGO Star Wars BB-8 Astromech Droid', 'lego-star-wars-bb-8-astromech-droid', 'STAR WARS LEGO DROID FIGURE – Inspire kids to create the LEGO Star Wars BB-8 Astromech Droid toy for 10+ year old boys and girls, capturing the charm of the character from The Force Awakens.', '80', '9', '17', 'img_69bd54828d6ee5.27048989.webp', '1', '0', '2026-04-17 14:21:37', '20', 'LEG-BB8', '0'),
('16', 'Science Mad 20cm Illuminated Night Globe', 'science-mad-20cm-illuminated-night-globe', 'And you\'re off exploring the world! The planet earth really is astonishing. It\'s difficult to imagine the vast distances between neighbouring continents, the wide oceans and seas that cover over 70% of its surface, and the near 200 countries that exist around the world. This 20cm diameter globe is a miniature model of our huge planet at your finger-tips, giving you access to continents, oceans, countries and major cities. It\'s a useful introduction to the fascinating science of geography, as well as being a convenient desktop reference.', '22', '0', '16', 'img_69bd5739b3ac33.55848488.webp', '1', '0', '2026-04-17 14:21:37', '20', 'DIS-GLOBE', '0'),
('17', 'Pokémon Mega Charizard X Ex Trading Card', 'pok-mon-mega-charizard-x-ex-trading-card', 'Under the right conditions - including a strong bond with its Trainer - Charizard can surpass its own limits and Mega Evolve! With this tin, you can choose the raging blue flames of Mega Charizard X ex, then find more Pokemon inside a handful of booster packs.', '22', '0', '15', 'img_69bd57c1b0b871.21861055.webp', '1', '0', '2026-04-17 14:21:37', '20', 'TOY-PKMN-CHAR', '0'),
('18', 'SIM Free Samsung Galaxy S26 Ultra 5G 512GB AI Phone Violet', 'sim-free-samsung-galaxy-s26-ultra-5g-512gb-ai-phone-violet', 'Meet Galaxy S26 Ultra. Featuring our most advanced display yet, dazzling camera innovation, and powerful Galaxy AI, it\'s here to help simplify your everyday. Your screen is personal by nature. And thanks to Privacy Display, it stays viewable only to you. Backed by the latest Qualcomm chipset built for Galaxy, it can perform deep searches and learn your habits with its Personal Data Engine. Galaxy S26 Ultra defines a new era of Galaxy AI phones – here to anticipate your every need and support you every step of the way. Just tap the Galaxy stars button to try out Galaxy AI\'s Photo Assist. Galaxy AI runs through every action – saving you time, keeping you connected and streamlining every day. And there\'s the all new Now Nudge, which gives content-aware suggestions related to you.', '1449', '1', '5', 'img_69bd589eb18a70.09464393.webp', '1', '0', '2026-04-17 14:21:37', '20', 'PHN-S26U-512', '0'),
('19', 'Petface House Scratcher', 'petface-house-scratcher', 'This Cat house scratcher gives your cat a place to have fun, exercise, explore, scratch and just relax. It features three different locations for them to relax including a house and a ladder.', '70', '13', '20', 'img_69bd632bc82e28.46608911.webp', '1', '0', '2026-04-17 14:21:37', '20', 'CAT-HOUSE', '0'),
('20', 'Cat Lounge and Play Scratcher', 'cat-lounge-and-play-scratcher', 'This cozy home they can call their own features a roomy condo, relaxing lounge basket, and two perfectly placed perches. Partially wrapped jute posts encourage satisfying cat-scratching sessions that can help maintain healthy nails. This cat tree is designed with overall stability in mind so you can feel good letting your fur friend climb, scratch, play, and nap to their heart\'s content.', '88', '11', '20', 'img_69bd63cfc64980.14864876.webp', '1', '0', '2026-04-17 14:21:37', '20', 'CAT-LOUNG', '0'),
('21', 'Ninja 7.6L Foodi Dual Zone Air Fryer and Dehydrator', 'ninja-7-6l-foodi-dual-zone-air-fryer-and-dehydrator', 'The air fryer that cooks 2 foods, 2 ways, and finishes at the same time. With 2 independent cooking zones, Sync to use different programs and times in each drawer - both finish cooking at the same time! Enjoy freshly cooked mains and sides together or cater to two different tastes. More people to feed? Match to make double the amount of food in the same amount of time! Uses little to no oil. 6 cooking functions. Cook from frozen. Extra-large 7.6L capacity. Dishwasher-safe parts.', '200', '21', '9', 'img_69cfa1d4e5f6c3.95570107.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'KIT-NINJA-AF', '0'),
('22', 'McGregor 23cm Cordless Grass Trimmer - 18V', 'mcgregor-23cm-cordless-grass-trimmer-18v', 'This McGregor 18V 23cm grass trimmer is ideal for cutting fine to coarse grass - anywhere. You can change the angle of the cutting head to enable you to edge your lawn, trim your grass and reach under objects such as benches and trampolines. The flower guard allows you to cut right up to the edge of your garden while protecting your flowers and plants. This grass trimmer has been designed with comfort in mind, featuring an adjustable front handle and telescopic shaft to adjust the trimmer to suit your height.', '36', '11', '10', 'img_69d25436496576.37754497.jpg', '1', '0', '2026-04-17 14:21:37', '20', 'GAR-MCGR-TRIM', '0'),
('23', 'The Book', 'the-book', 'The Book is a hand-illustrated guide to the discoveries, inventions, and social systems that have propelled our species forward. It is a celebration of human creativity and achievement, brought to you by artists, writers, researchers, and other makers of things, to be touched, shared, and passed from one hungry mind to another.', '99', '12', '23', 'img_69e8d00f114974.93305311.webp', '1', '1', '2026-04-22 14:41:35', '0', NULL, '0'),
('24', 'Men\'s Regular Fit Dress Shirt', 'men-s-regular-fit-dress-shirt', 'A shirt.', '24', '20', '7', 'img_69f8a569a01442.55140529.jpg', '1', '0', '2026-05-04 14:05:32', '20', 'MENS-REG-SHIRT', '1');

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rate_limits_lookup` (`action`,`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `remember_tokens` VALUES 
('6', '1', '24a95b1c92ab5b346091f10de927a7465d6a07d88d3fcd2447c5f2467142f5a0', '1780495454');

DROP TABLE IF EXISTS `return_items`;
CREATE TABLE `return_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL,
  `order_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `order_item_id` (`order_item_id`),
  CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`),
  CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `return_items` VALUES 
('1', '1', '21', '1'),
('2', '2', '22', '1'),
('3', '3', '25', '1'),
('4', '4', '18', '1'),
('5', '5', '17', '1'),
('6', '6', '16', '1'),
('7', '7', '26', '1'),
('8', '8', '27', '1'),
('9', '9', '29', '1'),
('10', '10', '28', '1'),
('11', '11', '30', '1'),
('12', '12', '31', '1'),
('13', '13', '32', '1'),
('14', '14', '35', '1');

DROP TABLE IF EXISTS `returns`;
CREATE TABLE `returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `reason` text,
  `refund_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `returns` VALUES 
('1', '18', '1', 'approved', 'Doesn\'t work.', '79.95', '2026-04-28 14:19:20', '2026-04-28 14:24:55'),
('2', '18', '1', 'rejected', 'It\'s fine.', '0.00', '2026-04-28 14:25:23', '2026-04-28 14:25:40'),
('3', '21', '1', 'approved', 'Wrong size.', '89.00', '2026-04-28 14:34:52', '2026-04-28 14:35:06'),
('4', '16', '1', 'approved', 'Wrong size.', '64.99', '2026-04-28 14:39:03', '2026-04-28 14:41:04'),
('5', '15', '1', 'rejected', 'It\'s fine.', '0.00', '2026-04-28 14:41:43', '2026-04-28 14:41:57'),
('6', '14', '1', 'approved', 'Wrong size.', '59.99', '2026-04-28 14:44:51', '2026-04-28 14:53:53'),
('7', '22', '1', 'approved', 'Not working.', '1099.00', '2026-04-28 14:54:36', '2026-04-28 14:55:21'),
('8', '23', '1', 'approved', 'Don\'t want it.', '303.94', '2026-04-28 15:04:43', '2026-04-28 15:06:46'),
('9', '24', '1', 'approved', 'I don\'t have a garden.', '42.99', '2026-04-28 15:07:59', '2026-04-28 15:08:22'),
('10', '24', '1', 'rejected', 'They\'re fine.', '0.00', '2026-04-28 15:08:55', '2026-04-28 15:11:18'),
('11', '25', '1', 'approved', 'I don\'t have a garden.', '24.99', '2026-04-28 15:53:47', '2026-04-28 15:57:54'),
('12', '25', '1', 'approved', 'Don\'t want it.', '26.99', '2026-04-28 16:08:25', '2026-04-28 16:08:35'),
('13', '26', '1', 'approved', 'Doens\'t work.', '79.95', '2026-04-28 16:09:47', '2026-04-28 16:09:57'),
('14', '29', '2', 'approved', 'Don\'t want it.', '200.00', '2026-04-28 17:03:31', '2026-04-28 17:03:44');

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `reviews` VALUES 
('1', '11', '1', '3', 'It works well.', 'approved', '2026-04-29 12:22:11'),
('2', '11', '1', '4', 'The iPhone 17 Pro Max impresses with excellent battery life, top-notch camera quality, smooth performance, and durability. Users appreciate its large, bright screen and reliable connectivity, though some find features limited and usability challenging.', 'approved', '2026-04-29 12:23:56'),
('3', '11', '1', '5', 'iphone review.', 'approved', '2026-04-29 12:26:24'),
('4', '22', '1', '5', 'Strimmer review.', 'approved', '2026-04-29 12:26:35'),
('5', '11', '1', '5', 'Bad review.', 'rejected', '2026-04-29 12:27:56');

DROP TABLE IF EXISTS `scheduled_tasks`;
CREATE TABLE `scheduled_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `last_run_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `scheduled_tasks` VALUES 
('1', 'recover-carts', '2026-05-04 01:48:00'),
('2', 'logs:rotate', '2026-05-04 01:48:00'),
('3', 'images:cleanup', '2026-05-04 15:23:00');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `settings` VALUES 
('currency_symbol', '£'),
('default_vat_rate', '20'),
('email_from', 'noreply@shop.local'),
('login_max_attempts', '5'),
('login_window_minutes', '15'),
('low_stock_threshold', '10'),
('mobile_nav_max_combined', '20'),
('mobile_nav_max_top', '10'),
('password_min_length', '6'),
('register_max_attempts', '10'),
('register_window_minutes', '60'),
('remember_me_days', '30'),
('site_name', 'Demo|shop'),
('site_url', 'http://shop-demo.test');

DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE `user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postcode` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `user_addresses` VALUES 
('1', '1', 'Home', 'Test Test', '1 Test St', 'Test', 'TS1 1ST', 'United Kingdom', '1', '2026-04-29 14:42:59'),
('2', '1', 'Work', 'Test Test', '2 Test St', 'Test', 'TS2 2ST', 'United Kingdom', '0', '2026-04-29 14:52:07');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'customer',
  `is_verified` tinyint(1) DEFAULT '0',
  `verification_token` text,
  `address` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` VALUES 
('1', 'Admin', 'admin@shop.local', '$2y$12$o5mW3mP8EnNS6HIhmirYdOMiNfkn.WaEHUKLWdtlzogwnA3hsaQWK', 'admin', '1', NULL, '1 Test St\r\nTest Town\r\nTest\r\nTS1 1ST', '2026-04-17 14:21:38'),
('2', 'Jane Smith', 'jane@example.com', '$2y$12$o5mW3mP8EnNS6HIhmirYdOMiNfkn.WaEHUKLWdtlzogwnA3hsaQWK', 'customer', '1', NULL, '2 Test St\r\nTest\r\nTS2 2ST', '2026-04-17 14:21:38');

DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE `wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `wishlists` VALUES 
('1', '1', '9', '2026-04-28 17:52:16');

SET FOREIGN_KEY_CHECKS=1;
