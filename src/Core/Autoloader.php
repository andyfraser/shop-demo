<?php
namespace App\Core;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            $prefixApp = 'App\\';
            $prefixPsr = 'Psr\\';
            $base_dir = __DIR__ . '/../';

            if (strncmp($prefixApp, $class, strlen($prefixApp)) === 0) {
                $relative_class = substr($class, strlen($prefixApp));
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            } elseif (strncmp($prefixPsr, $class, strlen($prefixPsr)) === 0) {
                $relative_class = substr($class, strlen($prefixPsr));
                $file = $base_dir . 'Psr/' . str_replace('\\', '/', $relative_class) . '.php';
            } else {
                return;
            }

            if (isset($file) && file_exists($file)) {
                require $file;
            }
        });
    }
}
