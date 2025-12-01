<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserAchievement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAchievement>
 */
class UserAchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAchievement::class);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['unlockedAt' => 'DESC']);
    }

    public function findByUserAndAchievementId(User $user, string $achievementId): ?UserAchievement
    {
        return $this->findOneBy([
            'user' => $user,
            'achievementId' => $achievementId,
        ]);
    }

    public function countByAchievementId(string $achievementId): int
    {
        return $this->count(['achievementId' => $achievementId]);
    }

    /**
     * Get recent achievements across all users
     *
     * @return UserAchievement[]
     */
    public function findRecentAchievements(int $limit = 10): array
    {
        return $this->createQueryBuilder('ua')
            ->orderBy('ua.unlockedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
