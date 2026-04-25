<?php
/**
 * Demoshop Configuration Example
 * 
 * Rename this file to config.php and update the values.
 */

return [
    'db' => [
        'driver'   => 'sqlite', // 'sqlite' or 'mysql'
        
        // MySQL settings
        'host'     => '127.0.0.1',
        'dbname'   => 'shop_demo',
        'user'     => 'root',
        'pass'     => '',
        'charset'  => 'utf8mb4',
        
        // SQLite settings
        'path'     => __DIR__ . '/shop.db',
    ],
    
    // Site settings
    'site' => [
        'base_url' => '',
    ],
    
    // Application settings
    'app' => [
        'debug' => false,
        'log_retention_days' => 30,
    ],
];
