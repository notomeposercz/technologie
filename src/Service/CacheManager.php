<?php
/**
 * Správce cache pro modul technologie
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Service;

class CacheManager
{
    private const CACHE_PREFIX = 'technologie_';
    private const DEFAULT_TTL = 3600; // 1 hodina

    /**
     * Získání dat z cache
     */
    public function get(string $key, callable $callback = null, int $ttl = self::DEFAULT_TTL)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        // Pokus o načtení z PrestaShop cache
        if (class_exists('\Cache')) {
            $cached = \Cache::retrieve($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Pokud není v cache a máme callback, zavoláme ho
        if ($callback && is_callable($callback)) {
            $data = $callback();
            $this->set($key, $data, $ttl);
            return $data;
        }

        return null;
    }

    /**
     * Uložení dat do cache
     */
    public function set(string $key, $data, int $ttl = self::DEFAULT_TTL): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        if (class_exists('\Cache')) {
            return \Cache::store($cacheKey, $data, $ttl);
        }

        return false;
    }

    /**
     * Smazání konkrétního klíče z cache
     */
    public function delete(string $key): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        if (class_exists('\Cache')) {
            return \Cache::clean($cacheKey);
        }

        return false;
    }

    /**
     * Vyčištění celé cache modulu
     */
    public function clearAll(): bool
    {
        if (class_exists('\Cache')) {
            return \Cache::clean(self::CACHE_PREFIX . '*');
        }

        return false;
    }

    /**
     * Získání seznamu aktivních technologií s cache
     */
    public function getActiveTechnologies(): array
    {
        return $this->get('active_technologies', function() {
            $repository = \Context::getContext()->get('doctrine.orm.entity_manager')
                ->getRepository('PrestaShop\Module\Technologie\Entity\Technologie');
            
            return $repository->findActiveOrderedByPosition();
        });
    }

    /**
     * Invalidace cache při změnách
     */
    public function invalidateTechnologiesCache(): void
    {
        $this->delete('active_technologies');
        $this->delete('all_technologies');
        $this->delete('technologies_count');
    }
}
