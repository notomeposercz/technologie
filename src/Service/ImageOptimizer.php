<?php
/**
 * Optimalizátor obrázků
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Service;

class ImageOptimizer
{
    private const MAX_WIDTH = 800;
    private const MAX_HEIGHT = 600;
    private const JPEG_QUALITY = 85;
    private const WEBP_QUALITY = 80;

    /**
     * Optimalizace nahraného obrázku
     */
    public function optimizeImage(string $sourcePath, string $targetPath): bool
    {
        try {
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return false;
            }

            [$width, $height, $type] = $imageInfo;

            // Vytvoření zdrojového obrázku
            $sourceImage = $this->createImageFromType($sourcePath, $type);
            if (!$sourceImage) {
                return false;
            }

            // Výpočet nových rozměrů
            [$newWidth, $newHeight] = $this->calculateNewDimensions($width, $height);

            // Vytvoření nového obrázku
            $targetImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Zachování průhlednosti pro PNG a GIF
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                $this->preserveTransparency($targetImage, $sourceImage, $type);
            }

            // Změna velikosti
            imagecopyresampled(
                $targetImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $width, $height
            );

            // Uložení optimalizovaného obrázku
            $result = $this->saveOptimizedImage($targetImage, $targetPath, $type);

            // Uvolnění paměti
            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            return $result;

        } catch (\Exception $e) {
            error_log('Image optimization error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vytvoření obrázku podle typu
     */
    private function createImageFromType(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Výpočet nových rozměrů se zachováním poměru stran
     */
    private function calculateNewDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return [$width, $height];
        }

        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        
        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio)
        ];
    }

    /**
     * Zachování průhlednosti
     */
    private function preserveTransparency($targetImage, $sourceImage, int $type): void
    {
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefill($targetImage, 0, 0, $transparent);
        } elseif ($type === IMAGETYPE_GIF) {
            $transparentIndex = imagecolortransparent($sourceImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);
                $transparentNew = imagecolorallocate(
                    $targetImage,
                    $transparentColor['red'],
                    $transparentColor['green'],
                    $transparentColor['blue']
                );
                imagefill($targetImage, 0, 0, $transparentNew);
                imagecolortransparent($targetImage, $transparentNew);
            }
        }
    }

    /**
     * Uložení optimalizovaného obrázku
     */
    private function saveOptimizedImage($image, string $path, int $type): bool
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $path, self::JPEG_QUALITY);
            case IMAGETYPE_PNG:
                return imagepng($image, $path, 9);
            case IMAGETYPE_GIF:
                return imagegif($image, $path);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $path, self::WEBP_QUALITY);
            default:
                return false;
        }
    }

    /**
     * Vytvoření WebP verze obrázku
     */
    public function createWebPVersion(string $sourcePath): ?string
    {
        $pathInfo = pathinfo($sourcePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }

        $sourceImage = $this->createImageFromType($sourcePath, $imageInfo[2]);
        if (!$sourceImage) {
            return null;
        }

        if (imagewebp($sourceImage, $webpPath, self::WEBP_QUALITY)) {
            imagedestroy($sourceImage);
            return $webpPath;
        }

        imagedestroy($sourceImage);
        return null;
    }
}
