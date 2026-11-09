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
