<?php
/**
 * Test repository bez PrestaShop závislostí
 */

echo "Testing repository...\n";

// Simulace základních konstant
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.2.0');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

// Mock funkce
if (!function_exists('pSQL')) {
    function pSQL($string) {
        return addslashes($string);
    }
}

// Mock Db class
class Db {
    public static function getInstance() {
        return new self();
    }
    
    public function executeS($sql) {
        echo "Mock executeS: $sql\n";
        return [];
    }
    
    public function getRow($sql) {
        echo "Mock getRow: $sql\n";
        return false;
    }
    
    public function execute($sql) {
        echo "Mock execute: $sql\n";
        return true;
    }
    
    public function Insert_ID() {
        return 1;
    }
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
    
    if (file_exists($file)) {
        require $file;
    }
});

echo "Testing TechnologieDbRepository...\n";

try {
    $repository = new PrestaShop\Module\Technologie\Repository\TechnologieDbRepository();
    echo "✓ TechnologieDbRepository created successfully\n";
    
    // Test findAllOrderedByPosition
    $technologie = $repository->findAllOrderedByPosition();
    echo "✓ findAllOrderedByPosition executed successfully\n";
    
    // Test vytvoření nové technologie
    $tech = new PrestaShop\Module\Technologie\Entity\Technologie();
    $tech->setName('Test technologie');
    $tech->setDescription('Test popis');
    $tech->setPosition(1);
    $tech->setActive(true);
    
    echo "✓ Technologie entity created successfully\n";
    
    // Test uložení (mock)
    $repository->save($tech);
    echo "✓ Save method executed successfully\n";
    
    // Test getMaxPosition
    $maxPos = $repository->getMaxPosition();
    echo "✓ getMaxPosition returned: $maxPos\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nRepository test completed.\n";
