<?php
/**
 * Jednoduchý test autoloaderu
 */

echo "Starting simple autoloader test...\n";

// Simulace základních PrestaShop konstant
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.2.0');
}

// Autoloader pro namespace PrestaShop\Module\Technologie
spl_autoload_register(function ($class) {
    $prefix = 'PrestaShop\\Module\\Technologie\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    echo "Trying to load: $file\n";
    
    if (file_exists($file)) {
        echo "File exists, requiring...\n";
        require $file;
        echo "File loaded successfully\n";
    } else {
        echo "File does not exist!\n";
    }
});

echo "Autoloader registered\n";

// Test načtení tříd
echo "Testing class loading...\n";

try {
    echo "Checking if Technologie class exists...\n";
    if (class_exists('PrestaShop\Module\Technologie\Entity\Technologie')) {
        echo "✓ Technologie Entity class loaded successfully\n";
    } else {
        echo "✗ Technologie Entity class NOT found\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
