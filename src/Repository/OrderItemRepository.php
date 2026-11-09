<?php

namespace App\Repository;

use App\Entity\Guitar;
use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /** In how many order lines does this guitar appear? (0 = safe to delete) */
    public function countForGuitar(Guitar $guitar): int
    {
        return (int) $this->createQueryBuilder('oi')
            ->select('COUNT(oi.id)')
            ->andWhere('oi.guitar = :guitar')
            ->setParameter('guitar', $guitar)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
