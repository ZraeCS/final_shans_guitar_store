<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 */
class CustomerOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    /**
     * Newest orders first, with the customer loaded in the same query.
     *
     * @return CustomerOrder[]
     */
    public function findRecent(int $limit = 6): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Orders page: newest first, optionally only one status, optionally
     * searching by order number or customer name / email.
     *
     * @return CustomerOrder[]
     */
    public function search(?OrderStatus $status = null, ?string $query = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $status->value);
        }

        $query = trim((string) $query);
        if ($query !== '') {
            $number = ltrim($query, '#');
            if (ctype_digit($number)) {
                // "17" or "#17" -> that order number
                $qb->andWhere('o.id = :id')->setParameter('id', (int) $number);
            } else {
                $qb->andWhere('c.name LIKE :q OR c.email LIKE :q OR o.fullname LIKE :q')
                   ->setParameter('q', '%'.$query.'%');
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * How many orders have each status, for the filter buttons.
     *
     * @return array<string, int> e.g. ['pending' => 4, 'shipped' => 2, ...]
     */
    public function countPerStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status, COUNT(o.id) AS total')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases()), 0);
        foreach ($rows as $row) {
            $key = $row['status'] instanceof OrderStatus ? $row['status']->value : (string) $row['status'];
            $counts[$key] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * GCash page, "To verify" list: oldest first, so nobody waits too long.
     *
     * @return CustomerOrder[]
     */
    public function findGcashToVerify(): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.customer', 'c')
            ->addSelect('c')
            ->andWhere('o.paymentMethod = :gcash')
            ->andWhere('o.paymentVerifiedAt IS NULL')
            ->andWhere('o.status = :pending')
            ->setParameter('gcash', PaymentMethod::Gcash->value)
            ->setParameter('pending', OrderStatus::Pending->value)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * GCash page, "Recently verified" list.
     *
     * @return CustomerOrder[]
     */
    public function findRecentlyVerifiedGcash(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.customer', 'c')
            ->addSelect('c')
            ->leftJoin('o.paymentVerifiedBy', 'v')
            ->addSelect('v')
            ->andWhere('o.paymentMethod = :gcash')
            ->andWhere('o.paymentVerifiedAt IS NOT NULL')
            ->setParameter('gcash', PaymentMethod::Gcash->value)
            ->orderBy('o.paymentVerifiedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Money from orders placed between $from (included) and $to (excluded).
     * Cancelled orders don't count (same rule as the store's old admin).
     */
    public function getSalesBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        return (float) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.createdAt >= :from')
            ->andWhere('o.createdAt < :to')
            ->andWhere('o.status != :cancelled')
            ->setParameter('from', $from, Types::DATETIME_IMMUTABLE)
            ->setParameter('to', $to, Types::DATETIME_IMMUTABLE)
            ->setParameter('cancelled', OrderStatus::Cancelled->value)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** How many orders were placed between $from (included) and $to (excluded). */
    public function countCreatedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.createdAt >= :from')
            ->andWhere('o.createdAt < :to')
            ->setParameter('from', $from, Types::DATETIME_IMMUTABLE)
            ->setParameter('to', $to, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countByStatus(OrderStatus $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status->value)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Pending GCash orders nobody has verified yet: the admin must check the
     * reference number in the GCash app before the order can be confirmed.
     */
    public function countGcashToVerify(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.paymentMethod = :gcash')
            ->andWhere('o.paymentVerifiedAt IS NULL')
            ->andWhere('o.status = :pending')
            ->setParameter('gcash', PaymentMethod::Gcash->value)
            ->setParameter('pending', OrderStatus::Pending->value)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
