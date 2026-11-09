<?php

namespace App\Repository;

use App\Entity\Guitar;
use App\Entity\OrderItem;
use App\Enum\OrderStatus;
use Doctrine\DBAL\Types\Types;
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

    /**
     * Sales Reports: best-selling products between $from and $to
     * (cancelled orders don't count), most units first.
     *
     * @return array<int, array{name: string, itemCode: string, units: int, revenue: string}>
     */
    public function findTopProducts(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('oi')
            ->select('g.name AS name, g.itemCode AS itemCode')
            ->addSelect('SUM(oi.quantity) AS units')
            ->addSelect('SUM(oi.quantity * oi.unitPrice) AS revenue')
            ->join('oi.customerOrder', 'o')
            ->join('oi.guitar', 'g')
            ->andWhere('o.createdAt >= :from')
            ->andWhere('o.createdAt < :to')
            ->andWhere('o.status != :cancelled')
            ->setParameter('from', $from, Types::DATETIME_IMMUTABLE)
            ->setParameter('to', $to, Types::DATETIME_IMMUTABLE)
            ->setParameter('cancelled', OrderStatus::Cancelled->value)
            ->groupBy('g.id, g.name, g.itemCode')
            ->orderBy('units', 'DESC')
            ->addOrderBy('revenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult()
        ;
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
