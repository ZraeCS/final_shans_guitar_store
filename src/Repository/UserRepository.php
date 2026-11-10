<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Enum\UserRole;
use App\Enum\UserStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Users page (admin): every account, optionally filtered by role, status
     * and a search on name or email. Admins first, then staff, then customers.
     *
     * @return User[]
     */
    public function searchAccounts(?string $query = null, ?UserRole $role = null, ?UserStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->addSelect("CASE WHEN u.role = 'admin' THEN 0 WHEN u.role = 'staff' THEN 1 ELSE 2 END AS HIDDEN roleOrder")
            ->orderBy('roleOrder', 'ASC')
            ->addOrderBy('u.name', 'ASC');

        $query = trim((string) $query);
        if ($query !== '') {
            $qb->andWhere('u.name LIKE :q OR u.email LIKE :q')->setParameter('q', '%'.$query.'%');
        }
        if ($role !== null) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role->value);
        }
        if ($status !== null) {
            $qb->andWhere('u.status = :status')->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }

    /** How many ACTIVE admins exist (the last one may not be removed, demoted or disabled). */
    public function countActiveAdmins(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.role = :admin')
            ->andWhere('u.status = :active')
            ->setParameter('admin', UserRole::Admin->value)
            ->setParameter('active', UserStatus::Active->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Customers page: each customer with their order statistics.
     * LEFT JOIN, so customers who never ordered still appear (0 orders).
     * "orders" and "spent" both ignore cancelled orders.
     *
     * @return array<int, array{customer: User, orders: int, spent: string, lastOrder: ?string}>
     */
    public function findCustomersWithStats(?string $query = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u AS customer')
            ->addSelect("COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN 1 ELSE 0 END), 0) AS orders")
            ->addSelect("COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total ELSE 0 END), 0) AS spent")
            ->addSelect('MAX(o.createdAt) AS lastOrder')
            ->leftJoin(CustomerOrder::class, 'o', 'WITH', 'o.customer = u')
            ->andWhere('u.role = :role')
            ->setParameter('role', UserRole::User->value)
            ->groupBy('u.id')
            ->orderBy('u.createdAt', 'DESC');

        $query = trim((string) $query);
        if ($query !== '') {
            $qb->andWhere('u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q')
               ->setParameter('q', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Customers only (role = user), newest first. Used by the Customers page.
     *
     * @return User[]
     */
    public function findCustomers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', UserRole::User->value)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
