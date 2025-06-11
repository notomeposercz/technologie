<?php
/**
 * Validátor formulářů
 * 
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Form;

use PrestaShop\Module\Technologie\Entity\Technologie;
use PrestaShop\Module\Technologie\Repository\TechnologieRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Třída pro validaci formulářových dat
 */
class FormValidator
{
    private TechnologieRepository $repository;

    /**
     * Konstruktor
     */
    public function __construct(TechnologieRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Validace dat technologie
     * 
     * @param array $data Data k validaci
     * @param Technologie|null $existingTechnologie Existující technologie (pro editaci)
     * @return array Pole s chybami
     */
    public function validateTechnologieData(array $data, ?Technologie $existingTechnologie = null): array
    {
        $errors = [];

        // Validace názvu
        if (empty($data['name'])) {
            $errors['name'] = 'Název technologie je povinný';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Název může mít maximálně 255 znaků';
        } else {
            // Kontrola unikátnosti názvu
            $existing = $this->repository->findOneBy(['name' => $data['name']]);
            if ($existing && (!$existingTechnologie || $existing->getId() !== $existingTechnologie->getId())) {
                $errors['name'] = 'Technologie s tímto názvem již existuje';
            }
        }

        // Validace popisu
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'Popis může mít maximálně 1000 znaků';
        }

        // Validace pozice
        if (isset($data['position'])) {
            if (!is_numeric($data['position']) || $data['position'] < 0) {
                $errors['position'] = 'Pořadí musí být kladné číslo nebo nula';
            }
        }

        return $errors;
    }

    /**
     * Validace obrázku
     * 
     * @param UploadedFile|null $file Nahrávaný soubor
     * @return array Pole s chybami
     */
    public function validateImage(?UploadedFile $file): array
    {
        $errors = [];

        if (!$file) {
            return $errors;
        }

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

        // Kontrola, zda je soubor skutečně obrázek
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            $errors[] = 'Nahraný soubor není platný obrázek';
        }

        return $errors;
    }

    /**
     * Sanitizace dat před uložením
     * 
     * @param array $data Data k sanitizaci
     * @return array Sanitizovaná data
     */
    public function sanitizeData(array $data): array
    {
        $sanitized = [];

        // Sanitizace názvu
        if (isset($data['name'])) {
            $sanitized['name'] = trim(strip_tags($data['name']));
        }

        // Sanitizace popisu (povolíme základní HTML tagy)
        if (isset($data['description'])) {
            $sanitized['description'] = trim($data['description']);
            // Povolíme pouze bezpečné HTML tagy
            $sanitized['description'] = strip_tags(
                $sanitized['description'], 
                '<p><br><strong><em><ul><ol><li>'
            );
        }

        // Sanitizace pozice
        if (isset($data['position'])) {
            $sanitized['position'] = max(0, (int) $data['position']);
        }

        // Sanitizace aktivního stavu
        if (isset($data['active'])) {
            $sanitized['active'] = (bool) $data['active'];
        }

        return $sanitized;
    }

    /**
     * Validace hromadných akcí
     * 
     * @param string $action Akce k provedení
     * @param array $ids Pole ID k zpracování
     * @return array Pole s chybami
     */
    public function validateBulkAction(string $action, array $ids): array
    {
        $errors = [];

        // Kontrola platnosti akce
        $allowedActions = ['activate', 'deactivate', 'delete'];
        if (!in_array($action, $allowedActions)) {
            $errors[] = 'Neplatná hromadná akce';
        }

        // Kontrola ID
        if (empty($ids)) {
            $errors[] = 'Nevybrali jste žádné položky';
        } else {
            // Kontrola, zda všechna ID jsou platná čísla
            foreach ($ids as $id) {
                if (!is_numeric($id) || $id <= 0) {
                    $errors[] = 'Neplatné ID položky: ' . $id;
                    break;
                }
            }
        }

        return $errors;
    }
}
