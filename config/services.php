<?php

/**
 * Dependency Injection Service Registrations
 * 
 * This file orchestrates the registration of all application services,
 * repositories, and core components by delegating to specialized files
 * in the config/services/ directory.
 */

return function(array $config) {
    $services = [];
    
    $registrationFiles = [
        __DIR__ . '/services/core.php',
        __DIR__ . '/services/events.php',
        __DIR__ . '/services/repositories.php',
        __DIR__ . '/services/services.php',
        __DIR__ . '/services/commands.php',
    ];

    foreach ($registrationFiles as $file) {
        if (file_exists($file)) {
            $registrar = require $file;
            // The registrar is a closure that returns an array of mappings
            // We pass null for the container initially as it's not needed for the mapping itself
            // but the container passes itself as the first argument when calling the factory closure.
            // Actually, the registrar closures I created take ($c, $config).
            // But here we are just collecting the factories.
            
            $mappings = $registrar(null, $config);
            $services = array_merge($services, $mappings);
        }
    }

    return $services;
};
