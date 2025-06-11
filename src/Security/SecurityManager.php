<?php
/**
 * Správce bezpečnosti pro modul technologie
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Security;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class SecurityManager
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_FILE_SIZE = 2097152; // 2MB v bytech

    /**
     * Validace nahrávaného obrázku
     */
    public function validateImageUpload(UploadedFile $file): array
    {
        $errors = [];

        // Kontrola velikosti souboru
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = sprintf(
                'Soubor je příliš velký. Maximální velikost je %s MB.',
                self::MAX_FILE_SIZE / 1024 / 1024
            );
        }

        // Kontrola MIME typu
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            $errors[] = 'Nepovolený typ souboru. Povolené jsou pouze obrázky (JPG, PNG, GIF, WebP).';
        }

        // Kontrola přípony
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $errors[] = 'Nepovolená přípona souboru.';
        }

        // Kontrola skutečného obsahu souboru
        if (!$this->isValidImageContent($file)) {
            $errors[] = 'Soubor není platný obrázek.';
        }

        return $errors;
    }

    /**
     * Kontrola skutečného obsahu obrázku
     */
    private function isValidImageContent(UploadedFile $file): bool
    {
        try {
            $imageInfo = getimagesize($file->getPathname());
            return $imageInfo !== false && $imageInfo[0] > 0 && $imageInfo[1] > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sanitizace názvu souboru
     */
    public function sanitizeFilename(string $filename): string
    {
        // Odstranění diakritiky
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
        
        // Převod na malá písmena
        $filename = strtolower($filename);
        
        // Nahrazení nepovolených znaků
        $filename = preg_replace('/[^a-z0-9\-_\.]/', '-', $filename);
        
        // Odstranění vícenásobných pomlček
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Odstranění pomlček na začátku a konci
        return trim($filename, '-');
    }

    /**
     * Generování bezpečného názvu souboru
     */
    public function generateSecureFilename(string $originalName): string
    {
        $pathInfo = pathinfo($originalName);
        $baseName = $this->sanitizeFilename($pathInfo['filename']);
        $extension = strtolower($pathInfo['extension']);
        
        // Přidání timestamp a náhodného řetězce
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return sprintf('%s_%s_%s.%s', $baseName, $timestamp, $random, $extension);
    }

    /**
     * Validace CSRF tokenu
     */
    public function validateCsrfToken(string $token, string $tokenId): bool
    {
        if (class_exists('\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface')) {
            $csrfTokenManager = \Context::getContext()->get('security.csrf.token_manager');
            return $csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($tokenId, $token));
        }
        
        // Fallback pro starší verze
        return Tools::getToken(false) === $token;
    }

    /**
     * Sanitizace HTML vstupu
     */
    public function sanitizeHtml(string $input): string
    {
        // Odstranění nebezpečných tagů
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li>');
        
        // Escapování speciálních znaků
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validace číselného vstupu
     */
    public function validateInteger(mixed $value, int $min = 0, int $max = PHP_INT_MAX): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        
        $intValue = (int) $value;
        
        if ($intValue < $min || $intValue > $max) {
            return null;
        }
        
        return $intValue;
    }

    /**
     * Kontrola oprávnění pro admin operace
     */
    public function checkAdminPermission(string $action = 'view'): bool
    {
        $employee = \Context::getContext()->employee;
        
        if (!$employee || !$employee->id) {
            return false;
        }

        // Kontrola oprávnění pro modul
        return $employee->hasAuthOnShop(\Context::getContext()->shop->id) &&
               \Module::isEnabled('technologie');
    }
}
