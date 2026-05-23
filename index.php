<?php
/**
 * Demoshop Root Wrapper
 * 
 * This file acts as the entry point when the web server document root is forced
 * to the project root directory rather than the public/ subdirectory. It secures
 * sensitive directories and delegates request execution to the actual front controller.
 */

// Force UTC for all internal operations
date_default_timezone_set('UTC');

// 1. Strict Security: Block direct access to sensitive directories and files
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Prevent direct browsing of source code, configurations, and templates
if (preg_match('#^/(src|config|templates|migrations|cli|storage|logs)(/|$)#', $path)) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

// Prevent direct download of database, log, markdown, and config files
if (preg_match('#\.(db|sqlite|log|ini|json|yml|yaml|md|txt)$#i', $path)) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

// 2. Delegate execution to the real front controller
require_once __DIR__ . '/public/index.php';
