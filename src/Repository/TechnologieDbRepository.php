<?php
/**
 * Fallback repository pro práci s technologiemi bez Doctrine
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Repository;

use PrestaShop\Module\Technologie\Entity\Technologie;

class TechnologieDbRepository
{
    /**
     * Najde všechny aktivní technologie seřazené podle pozice
     */
    public function findActiveOrderedByPosition(): array
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'technologie` 
                WHERE active = 1 
                ORDER BY position ASC, name ASC';
        
        $results = \Db::getInstance()->executeS($sql);
        
        if (!$results) {
            return [];
        }

        return $this->convertToEntities($results);
    }

    /**
     * Najde všechny technologie pro admin (včetně neaktivních)
     */
    public function findAllOrderedByPosition(): array
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'technologie` 
                ORDER BY position ASC, name ASC';
        
        $results = \Db::getInstance()->executeS($sql);
        
        if (!$results) {
            return [];
        }

        return $this->convertToEntities($results);
    }

    /**
     * Najde technologii podle ID
     */
    public function findOneById(int $id): ?Technologie
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'technologie` 
                WHERE id_technologie = ' . (int)$id;
        
        $result = \Db::getInstance()->getRow($sql);
        
        if (!$result) {
            return null;
        }

        return $this->convertToEntity($result);
    }

    /**
     * Uloží technologii
     */
    public function save(Technologie $technologie): void
    {
        if ($technologie->getId() !== null && $technologie->getId() > 0) {
            $this->update($technologie);
        } else {
            $this->insert($technologie);
        }
    }

    /**
     * Smaže technologii
     */
    public function delete(Technologie $technologie): void
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'technologie` 
                WHERE id_technologie = ' . (int)$technologie->getId();
        
        \Db::getInstance()->execute($sql);
    }

    /**
     * Získá nejvyšší pozici pro novou technologii
     */
    public function getMaxPosition(): int
    {
        $sql = 'SELECT MAX(position) as max_pos FROM `' . _DB_PREFIX_ . 'technologie`';
        $result = \Db::getInstance()->getRow($sql);
        
        return (int)($result['max_pos'] ?? 0);
    }

    /**
     * Hromadná aktivace/deaktivace
     */
    public function bulkUpdateActive(array $ids, bool $active): int
    {
        if (empty($ids)) {
            return 0;
        }

        $idsStr = implode(',', array_map('intval', $ids));
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'technologie` 
                SET active = ' . ($active ? 1 : 0) . ', 
                    date_upd = NOW() 
                WHERE id_technologie IN (' . $idsStr . ')';
        
        return \Db::getInstance()->execute($sql) ? count($ids) : 0;
    }

    /**
     * Aktualizace pozic pro drag & drop řazení
     */
    public function updatePositions(array $positions): void
    {
        foreach ($positions as $id => $position) {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'technologie` 
                    SET position = ' . (int)$position . ', 
                        date_upd = NOW() 
                    WHERE id_technologie = ' . (int)$id;
            
            \Db::getInstance()->execute($sql);
        }
    }

    /**
     * Vložení nové technologie
     */
    private function insert(Technologie $technologie): void
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'technologie`
                (name, description, image, position, active, date_add, date_upd)
                VALUES (
                    "' . pSQL($technologie->getName()) . '",
                    "' . pSQL($technologie->getDescription() ?? '') . '",
                    "' . pSQL($technologie->getImage() ?? '') . '",
                    ' . (int)$technologie->getPosition() . ',
                    ' . ($technologie->isActive() ? 1 : 0) . ',
                    NOW(),
                    NOW()
                )';
        
        if (\Db::getInstance()->execute($sql)) {
            $id = \Db::getInstance()->Insert_ID();
            // Nastavení ID do entity pomocí reflection (protože nemáme setter)
            $reflection = new \ReflectionClass($technologie);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($technologie, (int)$id);
        }
    }

    /**
     * Aktualizace existující technologie
     */
    private function update(Technologie $technologie): void
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'technologie` SET
                name = "' . pSQL($technologie->getName()) . '",
                description = "' . pSQL($technologie->getDescription() ?? '') . '",
                image = "' . pSQL($technologie->getImage() ?? '') . '",
                position = ' . (int)$technologie->getPosition() . ',
                active = ' . ($technologie->isActive() ? 1 : 0) . ',
                date_upd = NOW()
                WHERE id_technologie = ' . (int)$technologie->getId();
        
        \Db::getInstance()->execute($sql);
    }

    /**
     * Převod pole výsledků na entity
     */
    private function convertToEntities(array $results): array
    {
        $entities = [];
        foreach ($results as $result) {
            $entities[] = $this->convertToEntity($result);
        }
        return $entities;
    }

    /**
     * Převod řádku z databáze na entitu
     */
    private function convertToEntity(array $row): Technologie
    {
        $technologie = new Technologie();
        
        // Nastavení hodnot pomocí reflection
        $reflection = new \ReflectionClass($technologie);
        
        // ID
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($technologie, (int)$row['id_technologie']);
        
        // Ostatní vlastnosti
        $technologie->setName($row['name']);
        $technologie->setDescription($row['description']);
        $technologie->setImage($row['image']);
        $technologie->setPosition((int)$row['position']);
        $technologie->setActive((bool)$row['active']);
        
        // Datum
        $dateAddProperty = $reflection->getProperty('dateAdd');
        $dateAddProperty->setAccessible(true);
        $dateAddProperty->setValue($technologie, new \DateTime($row['date_add']));
        
        $dateUpdProperty = $reflection->getProperty('dateUpd');
        $dateUpdProperty->setAccessible(true);
        $dateUpdProperty->setValue($technologie, new \DateTime($row['date_upd']));
        
        return $technologie;
    }
}
