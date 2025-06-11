<?php
/**
 * Test script pro ověření autoloaderu
 */

echo "Starting autoloader test...\n";

// Simulace PrestaShop konstant
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', __DIR__ . '/../');
    echo "Defined _PS_MODULE_DIR_: " . _PS_MODULE_DIR_ . "\n";
}

// Načtení autoloaderu
echo "Loading technologie.php...\n";
require_once __DIR__ . '/technologie.php';
echo "technologie.php loaded successfully\n";

echo "Testing autoloader...\n";

// Test 1: Zkusíme načíst Entity třídu
try {
    if (class_exists('PrestaShop\Module\Technologie\Entity\Technologie')) {
        echo "✓ Technologie Entity class loaded successfully\n";
    } else {
        echo "✗ Technologie Entity class NOT found\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading Technologie Entity: " . $e->getMessage() . "\n";
}

// Test 2: Zkusíme načíst Repository třídu
try {
    if (class_exists('PrestaShop\Module\Technologie\Repository\TechnologieRepository')) {
        echo "✓ TechnologieRepository class loaded successfully\n";
    } else {
        echo "✗ TechnologieRepository class NOT found\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading TechnologieRepository: " . $e->getMessage() . "\n";
}

// Test 3: Zkusíme načíst Form třídu
try {
    if (class_exists('PrestaShop\Module\Technologie\Form\TechnologieType')) {
        echo "✓ TechnologieType class loaded successfully\n";
    } else {
        echo "✗ TechnologieType class NOT found\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading TechnologieType: " . $e->getMessage() . "\n";
}

// Test 4: Zkusíme vytvořit instanci Entity
try {
    $technologie = new PrestaShop\Module\Technologie\Entity\Technologie();
    echo "✓ Technologie Entity instance created successfully\n";
} catch (Exception $e) {
    echo "✗ Error creating Technologie Entity instance: " . $e->getMessage() . "\n";
}

echo "\nAutoloader test completed.\n";
