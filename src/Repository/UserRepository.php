<?php

namespace App\Repository;

use App\Entity\User;
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

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function countStaff(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int) $conn->fetchOne(
            'SELECT COUNT(id) FROM users WHERE CAST(roles AS text) LIKE :role',
            ['role' => '%ROLE_STAFF%']
        );
    }

    public function countAdmins(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int) $conn->fetchOne(
            'SELECT COUNT(id) FROM users WHERE CAST(roles AS text) LIKE :role',
            ['role' => '%ROLE_ADMIN%']
        );
    }

    public function countCustomers(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int) $conn->fetchOne(
            'SELECT COUNT(id) FROM users WHERE CAST(roles AS text) NOT LIKE :admin AND CAST(roles AS text) NOT LIKE :staff',
            [
                'admin' => '%ROLE_ADMIN%',
                'staff' => '%ROLE_STAFF%',
            ]
        );
    }

    public function findCustomers(?string $query = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT id FROM users WHERE CAST(roles AS text) NOT LIKE :admin AND CAST(roles AS text) NOT LIKE :staff';
        $ids = $conn->fetchFirstColumn($sql, [
            'admin' => '%ROLE_ADMIN%',
            'staff' => '%ROLE_STAFF%',
        ]);

        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('u.createdAt', 'DESC');

        if ($query) {
            $qb->andWhere('LOWER(u.name) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(u.username) LIKE :q')
               ->setParameter('q', '%' . strtolower($query) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countActiveCustomers(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int) $conn->fetchOne(
            'SELECT COUNT(id) FROM users WHERE CAST(roles AS text) NOT LIKE :admin AND CAST(roles AS text) NOT LIKE :staff AND is_active = true',
            ['admin' => '%ROLE_ADMIN%', 'staff' => '%ROLE_STAFF%']
        );
    }

    public function countInactiveCustomers(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int) $conn->fetchOne(
            'SELECT COUNT(id) FROM users WHERE CAST(roles AS text) NOT LIKE :admin AND CAST(roles AS text) NOT LIKE :staff AND is_active = false',
            ['admin' => '%ROLE_ADMIN%', 'staff' => '%ROLE_STAFF%']
        );
    }

    public function searchUsers(?string $query, ?string $roleFilter = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        if ($query) {
            $qb->andWhere('LOWER(u.name) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(u.username) LIKE :q')
               ->setParameter('q', '%' . strtolower($query) . '%');
        }

        if ($roleFilter) {
            $conn = $this->getEntityManager()->getConnection();
            $ids = $conn->fetchFirstColumn(
                'SELECT id FROM users WHERE CAST(roles AS text) LIKE :role',
                ['role' => '%' . $roleFilter . '%']
            );
            if (empty($ids)) {
                return [];
            }
            $qb->andWhere('u.id IN (:roleUserIds)')
               ->setParameter('roleUserIds', $ids);
        }

        return $qb->getQuery()->getResult();
    }
}
