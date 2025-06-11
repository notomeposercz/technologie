<?php
/**
 * Test script pro ověření image upload funkčnosti
 * Umístit do adresáře modules/technologie/
 */

echo "<html><head><title>Test Image Upload</title></head><body>";
echo "<h2>Test image upload funkčnosti modulu Technologie</h2>";

// Test upload adresáře
$uploadDir = __DIR__ . '/uploads/';

// 1. Test existence a oprávnění adresáře
echo "<h3>1. Test upload adresáře</h3>";
if (is_dir($uploadDir)) {
    echo "✅ Adresář existuje: $uploadDir<br>";
    
    if (is_writable($uploadDir)) {
        echo "✅ Adresář je zapisovatelný<br>";
    } else {
        echo "❌ Adresář NENÍ zapisovatelný<br>";
        echo "<strong>Řešení:</strong> Spusťte příkaz: <code>chmod 755 " . $uploadDir . "</code><br>";
    }
} else {
    echo "❌ Adresář neexistuje<br>";
    echo "Pokouším se vytvořit...<br>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✅ Adresář byl vytvořen<br>";
    } else {
        echo "❌ Nepodařilo se vytvořit adresář<br>";
        echo "<strong>Řešení:</strong> Ručně vytvořte adresář a nastavte oprávnění 755<br>";
    }
}

// 2. Test zápisu souboru
echo "<h3>2. Test zápisu souboru</h3>";
$testFile = $uploadDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'test obsah')) {
    echo "✅ Test zápisu úspěšný<br>";
    
    if (unlink($testFile)) {
        echo "✅ Test smazání úspěšný<br>";
    } else {
        echo "❌ Test smazání neúspěšný<br>";
    }
} else {
    echo "❌ Test zápisu neúspěšný<br>";
    echo "<strong>Řešení:</strong> Zkontrolujte oprávnění adresáře<br>";
}

// 3. Test .htaccess souboru
echo "<h3>3. Test .htaccess souboru</h3>";
$htaccessPath = $uploadDir . '.htaccess';
if (file_exists($htaccessPath)) {
    echo "✅ .htaccess soubor existuje<br>";
    echo "Obsah: <pre>" . htmlspecialchars(file_get_contents($htaccessPath)) . "</pre>";
} else {
    echo "❌ .htaccess soubor neexistuje<br>";
    echo "Vytvářím .htaccess...<br>";
    
    $htaccessContent = "Options -Indexes\n";
    $htaccessContent .= "RedirectMatch 403 \.php$\n";
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "✅ .htaccess soubor byl vytvořen<br>";
    } else {
        echo "❌ Nepodařilo se vytvořit .htaccess soubor<br>";
    }
}

// 4. Test PHP nastavení
echo "<h3>4. PHP nastavení pro upload</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Nastavení</strong></td><td><strong>Hodnota</strong></td><td><strong>Status</strong></td></tr>";

$uploadMaxSize = ini_get('upload_max_filesize');
echo "<tr><td>upload_max_filesize</td><td>$uploadMaxSize</td>";
echo "<td>" . (intval($uploadMaxSize) >= 2 ? "✅" : "❌ Mělo by být alespoň 2M") . "</td></tr>";

$postMaxSize = ini_get('post_max_size');
echo "<tr><td>post_max_size</td><td>$postMaxSize</td>";
echo "<td>" . (intval($postMaxSize) >= 2 ? "✅" : "❌ Mělo by být alespoň 2M") . "</td></tr>";

$maxFileUploads = ini_get('max_file_uploads');
echo "<tr><td>max_file_uploads</td><td>$maxFileUploads</td>";
echo "<td>" . ($maxFileUploads >= 1 ? "✅" : "❌") . "</td></tr>";

$fileUploads = ini_get('file_uploads');
echo "<tr><td>file_uploads</td><td>" . ($fileUploads ? 'Zapnuto' : 'Vypnuto') . "</td>";
echo "<td>" . ($fileUploads ? "✅" : "❌ Musí být zapnuto") . "</td></tr>";

$tmpDir = ini_get('upload_tmp_dir');
if (empty($tmpDir)) {
    $tmpDir = sys_get_temp_dir();
}
echo "<tr><td>upload_tmp_dir</td><td>$tmpDir</td>";
echo "<td>" . (is_writable($tmpDir) ? "✅ Zapisovatelný" : "❌ Není zapisovatelný") . "</td></tr>";

echo "</table>";

// 5. Test PHP rozšíření
echo "<h3>5. PHP rozšíření</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Rozšíření</strong></td><td><strong>Status</strong></td></tr>";

$extensions = [
    'gd' => 'GD (zpracování obrázků)',
    'fileinfo' => 'Fileinfo (detekce MIME typu)',
    'iconv' => 'Iconv (konverze znaků)'
];

foreach ($extensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    echo "<tr><td>$description</td><td>" . ($loaded ? "✅ Dostupné" : "❌ Nedostupné") . "</td></tr>";
}

echo "</table>";

// 6. Test aktuálního obsahu upload adresáře
echo "<h3>6. Obsah upload adresáře</h3>";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    if ($files && count($files) > 2) { // . a ..
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td><strong>Soubor</strong></td><td><strong>Velikost</strong></td><td><strong>Změněn</strong></td></tr>";
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $uploadDir . $file;
                $size = filesize($filePath);
                $modified = date('Y-m-d H:i:s', filemtime($filePath));
                echo "<tr><td>$file</td><td>" . formatBytes($size) . "</td><td>$modified</td></tr>";
            }
        }
        echo "</table>";
    } else {
        echo "Adresář je prázdný (obsahuje pouze . a ..)<br>";
    }
} else {
    echo "❌ Adresář neexistuje<br>";
}

// 7. Test simulace upload procesu
echo "<h3>7. Test HTML formuláře</h3>";
echo '<form action="" method="post" enctype="multipart/form-data">';
echo '<input type="file" name="test_image" accept="image/*" />';
echo '<input type="submit" name="test_upload" value="Test Upload" />';
echo '</form>';

// Zpracování test uploadu
if (isset($_POST['test_upload']) && isset($_FILES['test_image'])) {
    echo "<h4>Výsledek test uploadu:</h4>";
    $file = $_FILES['test_image'];
    
    echo "<strong>Informace o souboru:</strong><br>";
    echo "Název: " . htmlspecialchars($file['name']) . "<br>";
    echo "Typ: " . htmlspecialchars($file['type']) . "<br>";
    echo "Velikost: " . formatBytes($file['size']) . "<br>";
    echo "Chyba: " . $file['error'] . "<br>";
    echo "Dočasný soubor: " . $file['tmp_name'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "✅ Upload proběhl bez chyb<br>";
        
        // Test MIME typu
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            echo "Detekovaný MIME typ: $mimeType<br>";
        }
        
        // Test že je to obrázek
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo !== false) {
            echo "✅ Soubor je platný obrázek (" . $imageInfo[0] . "x" . $imageInfo[1] . ")<br>";
            
            // Test přesunu souboru
            $testFilename = 'test_' . time() . '_' . $file['name'];
            $targetPath = $uploadDir . $testFilename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                echo "✅ Soubor byl úspěšně přesunut do: $targetPath<br>";
                chmod($targetPath, 0644);
                echo "✅ Oprávnění nastavena na 644<br>";
                
                // Zobrazení náhledu
                $webPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath);
                echo "Náhled: <br><img src='$webPath' style='max-width: 200px; max-height: 200px;' /><br>";
                
                // Automatické smazání test souboru po 10 sekundách
                echo "<script>setTimeout(function() { 
                    fetch('?delete_test=" . urlencode($testFilename) . "'); 
                    location.reload(); 
                }, 10000);</script>";
                echo "<em>Test soubor bude automaticky smazán za 10 sekund...</em><br>";
                
            } else {
                echo "❌ Nepodařilo se přesunout soubor<br>";
            }
        } else {
            echo "❌ Soubor není platný obrázek<br>";
        }
    } else {
        echo "❌ Chyba při uploadu: ";
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "Soubor překračuje upload_max_filesize";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "Soubor překračuje MAX_FILE_SIZE";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "Soubor byl nahrán pouze částečně";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "Nebyl vybrán žádný soubor";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "Chybí dočasný adresář";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "Nelze zapsat na disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "Upload zastaven rozšířením";
                break;
            default:
                echo "Neznámá chyba ($file[error])";
        }
        echo "<br>";
    }
}

// Zpracování smazání test souboru
if (isset($_GET['delete_test'])) {
    $filename = $_GET['delete_test'];
    $filePath = $uploadDir . basename($filename); // basename pro bezpečnost
    if (file_exists($filePath) && strpos($filename, 'test_') === 0) {
        unlink($filePath);
        echo "Test soubor smazán<br>";
    }
    exit;
}

// Helper funkce pro formátování velikosti
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

echo "<hr>";
echo "<h3>Souhrn</h3>";
echo "<p>Pokud všechny testy prošly úspěšně (✅), upload obrázků by měl fungovat správně.</p>";
echo "<p>Pokud nějaký test selhal (❌), postupujte podle uvedených řešení.</p>";

echo "<h4>Nejčastější problémy:</h4>";
echo "<ul>";
echo "<li><strong>Oprávnění:</strong> Adresář uploads/ musí mít oprávnění 755</li>";
echo "<li><strong>PHP limity:</strong> upload_max_filesize a post_max_size musí být alespoň 2M</li>";
echo "<li><strong>Rozšíření:</strong> GD a fileinfo musí být dostupné</li>";
echo "<li><strong>Webserver:</strong> Zkontrolujte mod_rewrite a .htaccess</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Test dokončen - " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";