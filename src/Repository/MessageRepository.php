<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function countUnread(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.status = :status')
            ->setParameter('status', 'unread')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.status', 'DESC') // 'unread' before 'read' alphabetically
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
