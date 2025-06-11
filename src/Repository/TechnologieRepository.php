<?php
/**
 * Repository pro práci s technologiemi
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Repository;

use Doctrine\ORM\EntityRepository;
use PrestaShop\Module\Technologie\Entity\Technologie;

class TechnologieRepository extends EntityRepository
{
    /**
     * Najde všechny aktivní technologie seřazené podle pozice
     */
    public function findActiveOrderedByPosition(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.active = :active')
            ->setParameter('active', true)
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Najde všechny technologie pro admin (včetně neaktivních)
     */
    public function findAllOrderedByPosition(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Najde technologii podle ID
     */
    public function findOneById(int $id): ?Technologie
    {
        return $this->find($id);
    }

    /**
     * Uloží technologii
     */
    public function save(Technologie $technologie): void
    {
        $this->getEntityManager()->persist($technologie);
        $this->getEntityManager()->flush();
    }

    /**
     * Smaže technologii
     */
    public function delete(Technologie $technologie): void
    {
        $this->getEntityManager()->remove($technologie);
        $this->getEntityManager()->flush();
    }

    /**
     * Získá nejvyšší pozici pro novou technologii
     */
    public function getMaxPosition(): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->getQuery()
            ->getSingleScalarResult();
            
        return (int) $result;
    }

    /**
     * Hromadná aktivace/deaktivace
     */
    public function bulkUpdateActive(array $ids, bool $active): int
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.active', ':active')
            ->set('t.dateUpd', ':dateUpd')
            ->where('t.id IN (:ids)')
            ->setParameter('active', $active)
            ->setParameter('dateUpd', new \DateTime())
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * Aktualizace pozic pro drag & drop řazení
     */
    public function updatePositions(array $positions): void
    {
        foreach ($positions as $id => $position) {
            $this->createQueryBuilder('t')
                ->update()
                ->set('t.position', ':position')
                ->set('t.dateUpd', ':dateUpd')
                ->where('t.id = :id')
                ->setParameter('position', $position)
                ->setParameter('dateUpd', new \DateTime())
                ->setParameter('id', $id)
                ->getQuery()
                ->execute();
        }
    }
}
