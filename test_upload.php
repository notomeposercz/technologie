<?php
/**
 * Test script pro ověření funkčnosti upload adresáře
 */

// Kontrola upload adresáře
$uploadDir = __DIR__ . '/uploads/';

echo "<h2>Test upload adresáře pro modul Technologie</h2>";

// Kontrola existence adresáře
if (is_dir($uploadDir)) {
    echo "✅ Upload adresář existuje: " . $uploadDir . "<br>";
} else {
    echo "❌ Upload adresář neexistuje: " . $uploadDir . "<br>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✅ Upload adresář byl vytvořen<br>";
    } else {
        echo "❌ Nepodařilo se vytvořit upload adresář<br>";
    }
}

// Kontrola oprávnění
if (is_writable($uploadDir)) {
    echo "✅ Upload adresář je zapisovatelný<br>";
} else {
    echo "❌ Upload adresář není zapisovatelný<br>";
    echo "Zkuste nastavit oprávnění: chmod 755 " . $uploadDir . "<br>";
}

// Kontrola .htaccess
$htaccessFile = $uploadDir . '.htaccess';
if (file_exists($htaccessFile)) {
    echo "✅ .htaccess soubor existuje<br>";
} else {
    echo "❌ .htaccess soubor neexistuje<br>";
}

// Test vytvoření testovacího souboru
$testFile = $uploadDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'Test obsah')) {
    echo "✅ Test zápisu do adresáře úspěšný<br>";
    
    // Smazání testovacího souboru
    if (unlink($testFile)) {
        echo "✅ Test smazání souboru úspěšný<br>";
    } else {
        echo "❌ Test smazání souboru neúspěšný<br>";
    }
} else {
    echo "❌ Test zápisu do adresáře neúspěšný<br>";
}

// Výpis obsahu adresáře
echo "<h3>Obsah upload adresáře:</h3>";
$files = scandir($uploadDir);
if ($files) {
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            $size = filesize($filePath);
            $modified = date('Y-m-d H:i:s', filemtime($filePath));
            echo "📄 {$file} ({$size} bytes, {$modified})<br>";
        }
    }
} else {
    echo "Adresář je prázdný nebo nelze číst<br>";
}

echo "<br><strong>Test dokončen</strong>";
?>
