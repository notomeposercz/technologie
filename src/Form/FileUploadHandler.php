<?php
/**
 * Handler pro upload obrázků
 * 
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Form;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Třída pro správu upload obrázků
 */
class FileUploadHandler
{
    private string $uploadDir;

    /**
     * Konstruktor - inicializace upload adresáře
     */
    public function __construct()
    {
        $this->uploadDir = _PS_MODULE_DIR_ . 'technologie/uploads/';
        
        // Vytvoření adresáře pokud neexistuje
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload obrázku a vrácení názvu souboru
     * 
     * @param UploadedFile $file Nahrávaný soubor
     * @return string Název uloženého souboru
     * @throws \Exception Pokud se upload nepodaří
     */
    public function uploadImage(UploadedFile $file): string
    {
        // Generování unikátního názvu souboru
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugify($originalFilename);
        $extension = $file->guessExtension();
        $newFilename = $safeFilename . '_' . uniqid() . '.' . $extension;

        try {
            // Upload souboru
            $file->move($this->uploadDir, $newFilename);
            return $newFilename;
        } catch (\Exception $e) {
            throw new \Exception('Chyba při nahrávání souboru: ' . $e->getMessage());
        }
    }

    /**
     * Smazání obrázku
     * 
     * @param string $filename Název souboru k smazání
     * @return bool True pokud se smazání podařilo
     */
    public function deleteImage(string $filename): bool
    {
        if (empty($filename)) {
            return true;
        }

        $filePath = $this->uploadDir . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true; // Soubor neexistuje, považujeme za úspěch
    }

    /**
     * Kontrola existence obrázku
     * 
     * @param string $filename Název souboru
     * @return bool True pokud soubor existuje
     */
    public function imageExists(string $filename): bool
    {
        if (empty($filename)) {
            return false;
        }

        return file_exists($this->uploadDir . $filename);
    }

    /**
     * Získání cesty k upload adresáři
     * 
     * @return string Cesta k adresáři
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Získání URL k upload adresáři
     * 
     * @return string URL k adresáři
     */
    public function getUploadUrl(): string
    {
        return _MODULE_DIR_ . 'technologie/uploads/';
    }

    /**
     * Získání plné URL k obrázku
     * 
     * @param string $filename Název souboru
     * @return string|null URL k obrázku nebo null pokud soubor neexistuje
     */
    public function getImageUrl(string $filename): ?string
    {
        if (empty($filename) || !$this->imageExists($filename)) {
            return null;
        }

        return $this->getUploadUrl() . $filename;
    }

    /**
     * Validace obrázku
     * 
     * @param UploadedFile $file Soubor k validaci
     * @return array Pole s chybami (prázdné pokud je vše v pořádku)
     */
    public function validateImage(UploadedFile $file): array
    {
        $errors = [];

        // Kontrola velikosti souboru (max 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            $errors[] = 'Obrázek může mít maximálně 2MB';
        }

        // Kontrola typu souboru
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $errors[] = 'Povolené formáty: JPG, PNG, GIF, WebP';
        }

        return $errors;
    }

    /**
     * Převod názvu souboru na URL-safe string
     * 
     * @param string $text Text k převodu
     * @return string Převedený text
     */
    private function slugify(string $text): string
    {
        // Nahrazení diakritiky
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        
        // Odstranění speciálních znaků
        $text = preg_replace('/[^A-Za-z0-9\-]/', '', $text);
        
        // Odstranění vícenásobných pomlček
        $text = preg_replace('/-+/', '-', $text);
        
        // Odstranění pomlček na začátku a konci
        $text = trim($text, '-');

        // Pokud je výsledek prázdný, použij fallback
        if (empty($text)) {
            $text = 'image';
        }

        return $text;
    }
}
