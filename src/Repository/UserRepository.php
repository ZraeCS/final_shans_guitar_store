<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Enum\UserRole;
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
