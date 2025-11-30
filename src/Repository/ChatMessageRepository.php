<?php

namespace App\Repository;

use App\Entity\Cat;
use App\Entity\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Find all messages for a specific cat and session, ordered by creation time
     *
     * @return ChatMessage[]
     */
    public function findByCatAndSession(Cat $cat, string $sessionId, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.cat = :cat')
            ->andWhere('m.sessionId = :sessionId')
            ->setParameter('cat', $cat)
            ->setParameter('sessionId', $sessionId)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Clear all messages for a specific cat and session
     */
    public function clearByCatAndSession(Cat $cat, string $sessionId): int
    {
        return $this->createQueryBuilder('m')
            ->delete()
            ->andWhere('m.cat = :cat')
            ->andWhere('m.sessionId = :sessionId')
            ->setParameter('cat', $cat)
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->execute();
    }

    /**
     * Get recent messages for context (for AI)
     *
     * @return ChatMessage[]
     */
    public function getRecentMessages(Cat $cat, string $sessionId, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.cat = :cat')
            ->andWhere('m.sessionId = :sessionId')
            ->setParameter('cat', $cat)
            ->setParameter('sessionId', $sessionId)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
