<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function searchAndFilter(?string $search = null, ?string $category = null, ?string $stockStatus = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.createdBy', 'u')
            ->addSelect('u')
            ->orderBy('p.id', 'ASC');

        if ($search) {
            $qb->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.description) LIKE :search OR LOWER(p.category) LIKE :search')
               ->setParameter('search', '%' . strtolower($search) . '%');
        }

        if ($category && $category !== 'all') {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        if ($stockStatus === 'low') {
            $qb->andWhere('p.stock > 0 AND p.stock <= 3');
        } elseif ($stockStatus === 'out') {
            $qb->andWhere('p.stock <= 0');
        } elseif ($stockStatus === 'in') {
            $qb->andWhere('p.stock > 3');
        }

        return $qb->getQuery()->getResult();
    }

    public function countLowStock(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock > 0 AND p.stock <= 3')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOutOfStock(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock <= 0')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFeatured(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
