<?php
// router.php — used only with PHP's built-in server:
//   php -S localhost:8080 router.php
//
// Tells the server to serve static files (CSS, JS, images) directly,
// and route everything else through PHP as normal.

$uri = $_SERVER['REQUEST_URI'];
$file = __DIR__ . parse_url($uri, PHP_URL_PATH);

if (is_file($file)) {
    return false; // serve the file as-is
}

// Route to the requested PHP file
$path = parse_url($uri, PHP_URL_PATH);
$phpFile = __DIR__ . $path;
if (is_file($phpFile)) {
    require $phpFile;
} else {
    require __DIR__ . '/index.php';
}
