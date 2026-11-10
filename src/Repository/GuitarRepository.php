<?php

namespace App\Repository;

use App\Entity\Guitar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guitar>
 */
class GuitarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guitar::class);
    }

    /**
     * Guitars with LOW_STOCK_THRESHOLD units or fewer, emptiest first.
     *
     * @return Guitar[]
     */
    public function findLowStock(int $threshold = Guitar::LOW_STOCK_THRESHOLD): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('g.stock', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Products page list: optional text search (name, brand or item code)
     * and optional category filter, like the professor's findByCategory().
     *
     * @return Guitar[]
     */
    public function search(?string $query = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.createdBy', 'u')
            ->addSelect('u')
            ->orderBy('g.name', 'ASC');

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('g.name LIKE :q OR g.brand LIKE :q OR g.itemCode LIKE :q')
               ->setParameter('q', '%'.trim($query).'%');
        }

        if ($category !== null && $category !== '') {
            $qb->andWhere('g.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /** Suggests the next free item code: SG-0016 exists -> "SG-0017". */
    public function suggestNextItemCode(): string
    {
        $codes = $this->createQueryBuilder('g')
            ->select('g.itemCode')
            ->andWhere('g.itemCode LIKE :prefix')
            ->setParameter('prefix', 'SG-%')
            ->getQuery()
            ->getSingleColumnResult();

        $max = 0;
        foreach ($codes as $code) {
            $max = max($max, (int) substr($code, 3));
        }

        return sprintf('SG-%04d', $max + 1);
    }

    /** Total number of units on the shelves (all guitars added up). */
    public function countUnitsInStock(): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COALESCE(SUM(g.stock), 0)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
