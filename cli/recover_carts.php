<?php
require_once __DIR__ . '/../src/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Container;
use App\Services\EmailServiceInterface;

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/../config/config.php')) {
    $config = require __DIR__ . '/../config/config.php';
}
if (!defined('DB_CONFIG')) {
    define('DB_CONFIG', $config['db'] ?? ['driver' => 'sqlite', 'path' => __DIR__ . '/../shop.db']);
}

$container = new Container();
$servicesFactory = require __DIR__ . '/../config/services.php';
$services = $servicesFactory($config);
foreach ($services as $id => $factory) {
    $container->set($id, $factory);
}

$db = $container->get(\PDO::class);
$emailService = $container->get(EmailServiceInterface::class);

$driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

if ($driver === 'mysql') {
    $sql = "
        SELECT c.id, u.email, u.name 
        FROM carts c
        JOIN users u ON c.user_id = u.id
        WHERE c.last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)
          AND c.recovery_email_sent_at IS NULL
          AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
    ";
} else {
    $sql = "
        SELECT c.id, u.email, u.name 
        FROM carts c
        JOIN users u ON c.user_id = u.id
        WHERE c.last_activity < datetime('now', '-24 hours')
          AND c.recovery_email_sent_at IS NULL
          AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
    ";
}

$stmt = $db->query($sql);
$abandonedCarts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "Found " . count($abandonedCarts) . " abandoned carts.\n";

foreach ($abandonedCarts as $cart) {
    echo "Sending recovery email to {$cart['email']}... ";
    
    try {
        $success = $emailService->sendAbandonedCartEmail($cart['email'], $cart['name']);
        
        if ($success) {
            $update = $db->prepare("UPDATE carts SET recovery_email_sent_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update->execute([$cart['id']]);
            echo "Done.\n";
        } else {
            echo "Failed (mail() returned false).\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
