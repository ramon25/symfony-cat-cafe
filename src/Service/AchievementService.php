<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserAchievement;
use App\Repository\UserAchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AchievementService
{
    public const ACHIEVEMENTS = [
        'first_feeding' => [
            'id' => 'first_feeding',
            'name' => 'First Meal',
            'description' => 'Feed a cat for the first time',
            'emoji' => '🍽️',
            'points' => 10,
        ],
        'first_petting' => [
            'id' => 'first_petting',
            'name' => 'Gentle Touch',
            'description' => 'Pet a cat for the first time',
            'emoji' => '🤗',
            'points' => 10,
        ],
        'first_play' => [
            'id' => 'first_play',
            'name' => 'Playtime!',
            'description' => 'Play with a cat for the first time',
            'emoji' => '🧶',
            'points' => 10,
        ],
        'cat_whisperer' => [
            'id' => 'cat_whisperer',
            'name' => 'Cat Whisperer',
            'description' => 'Get wisdom from 3 different cats',
            'emoji' => '🔮',
            'points' => 25,
        ],
        'therapy_champion' => [
            'id' => 'therapy_champion',
            'name' => 'Therapy Champion',
            'description' => 'Have 5 therapy sessions',
            'emoji' => '🧠',
            'points' => 30,
        ],
        'social_butterfly' => [
            'id' => 'social_butterfly',
            'name' => 'Social Butterfly',
            'description' => 'Interact with 5 different cats',
            'emoji' => '🦋',
            'points' => 20,
        ],
        'dedicated_visitor' => [
            'id' => 'dedicated_visitor',
            'name' => 'Dedicated Visitor',
            'description' => 'Perform 20 interactions total',
            'emoji' => '⭐',
            'points' => 25,
        ],
        'best_friends' => [
            'id' => 'best_friends',
            'name' => 'Best Friends',
            'description' => 'Reach 80% bonding with any cat',
            'emoji' => '💕',
            'points' => 50,
        ],
        'quiz_master' => [
            'id' => 'quiz_master',
            'name' => 'Quiz Master',
            'description' => 'Complete your first compatibility quiz',
            'emoji' => '📝',
            'points' => 15,
        ],
        'perfect_match' => [
            'id' => 'perfect_match',
            'name' => 'Perfect Match',
            'description' => 'Score 80%+ on a compatibility quiz',
            'emoji' => '🌟',
            'points' => 30,
        ],
        'foster_parent' => [
            'id' => 'foster_parent',
            'name' => 'Foster Parent',
            'description' => 'Start fostering a cat',
            'emoji' => '🏡',
            'points' => 40,
        ],
        'forever_home' => [
            'id' => 'forever_home',
            'name' => 'Forever Home',
            'description' => 'Adopt a cat!',
            'emoji' => '🏠',
            'points' => 100,
        ],
    ];

    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private EntityManagerInterface $entityManager,
        private UserAchievementRepository $userAchievementRepository,
    ) {
    }

    private function getSession(): ?\Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    public function getUnlockedAchievements(): array
    {
        $user = $this->getUser();

        if ($user) {
            // For authenticated users, get from database
            $achievements = [];
            foreach ($user->getAchievements() as $achievement) {
                $achievements[] = $achievement->getAchievementId();
            }
            return $achievements;
        }

        // For anonymous users, fall back to session
        $session = $this->getSession();
        return $session ? $session->get('achievements', []) : [];
    }

    public function hasAchievement(string $achievementId): bool
    {
        $user = $this->getUser();

        if ($user) {
            return $user->hasAchievement($achievementId);
        }

        return in_array($achievementId, $this->getUnlockedAchievements());
    }

    public function unlockAchievement(string $achievementId): bool
    {
        if (!isset(self::ACHIEVEMENTS[$achievementId])) {
            return false;
        }

        if ($this->hasAchievement($achievementId)) {
            return false; // Already unlocked
        }

        $user = $this->getUser();
        $achievementData = self::ACHIEVEMENTS[$achievementId];

        if ($user) {
            // For authenticated users, persist to database
            $achievement = new UserAchievement();
            $achievement->setUser($user);
            $achievement->setAchievementId($achievementId);
            $achievement->setName($achievementData['name']);
            $achievement->setPoints($achievementData['points']);

            $this->entityManager->persist($achievement);
            $this->entityManager->flush();

            // Store newly unlocked for flash display
            $session = $this->getSession();
            if ($session) {
                $newlyUnlocked = $session->get('newly_unlocked_achievements', []);
                $newlyUnlocked[] = $achievementId;
                $session->set('newly_unlocked_achievements', $newlyUnlocked);
            }

            return true;
        }

        // For anonymous users, store in session
        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        $achievements = $session->get('achievements', []);
        $achievements[] = $achievementId;
        $session->set('achievements', $achievements);

        // Store the newly unlocked achievement for flash display
        $newlyUnlocked = $session->get('newly_unlocked_achievements', []);
        $newlyUnlocked[] = $achievementId;
        $session->set('newly_unlocked_achievements', $newlyUnlocked);

        return true;
    }

    public function getNewlyUnlockedAchievements(): array
    {
        $session = $this->getSession();
        if (!$session) {
            return [];
        }

        $newlyUnlocked = $session->get('newly_unlocked_achievements', []);
        $session->remove('newly_unlocked_achievements');

        return array_map(fn($id) => self::ACHIEVEMENTS[$id] ?? null, $newlyUnlocked);
    }

    public function getTotalPoints(): int
    {
        $user = $this->getUser();

        if ($user) {
            return $user->getTotalAchievementPoints();
        }

        $unlocked = $this->getUnlockedAchievements();
        $points = 0;
        foreach ($unlocked as $achievementId) {
            if (isset(self::ACHIEVEMENTS[$achievementId])) {
                $points += self::ACHIEVEMENTS[$achievementId]['points'];
            }
        }
        return $points;
    }

    public function getAchievementProgress(): array
    {
        $unlocked = $this->getUnlockedAchievements();
        $all = self::ACHIEVEMENTS;

        return [
            'unlocked' => count($unlocked),
            'total' => count($all),
            'percentage' => count($all) > 0 ? round((count($unlocked) / count($all)) * 100) : 0,
        ];
    }

    public function getAllAchievementsWithStatus(): array
    {
        $unlocked = $this->getUnlockedAchievements();
        $result = [];

        foreach (self::ACHIEVEMENTS as $id => $achievement) {
            $result[] = array_merge($achievement, [
                'unlocked' => in_array($id, $unlocked),
            ]);
        }

        return $result;
    }

    // Tracking stats for achievement unlocks
    public function incrementStat(string $statName, int $catId = null): void
    {
        $user = $this->getUser();

        if ($user) {
            // For authenticated users, persist stats to user entity
            $stats = $user->getAchievementStats() ?? [];

            if (!isset($stats[$statName])) {
                $stats[$statName] = ['count' => 0, 'cats' => []];
            }

            $stats[$statName]['count']++;

            if ($catId !== null && !in_array($catId, $stats[$statName]['cats'])) {
                $stats[$statName]['cats'][] = $catId;
            }

            $user->setAchievementStats($stats);
            $this->entityManager->flush();

            // Check for achievement unlocks based on this stat
            $this->checkStatBasedAchievements($stats);
            return;
        }

        // For anonymous users, use session
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $stats = $session->get('achievement_stats', []);

        if (!isset($stats[$statName])) {
            $stats[$statName] = ['count' => 0, 'cats' => []];
        }

        $stats[$statName]['count']++;

        if ($catId !== null && !in_array($catId, $stats[$statName]['cats'])) {
            $stats[$statName]['cats'][] = $catId;
        }

        $session->set('achievement_stats', $stats);

        // Check for achievement unlocks based on this stat
        $this->checkStatBasedAchievements($stats);
    }

    public function getStat(string $statName): array
    {
        $user = $this->getUser();

        if ($user) {
            $stats = $user->getAchievementStats() ?? [];
            return $stats[$statName] ?? ['count' => 0, 'cats' => []];
        }

        $session = $this->getSession();
        if (!$session) {
            return ['count' => 0, 'cats' => []];
        }

        $stats = $session->get('achievement_stats', []);
        return $stats[$statName] ?? ['count' => 0, 'cats' => []];
    }

    private function checkStatBasedAchievements(array $stats): void
    {
        // First feeding/petting/play
        if (($stats['feed']['count'] ?? 0) >= 1) {
            $this->unlockAchievement('first_feeding');
        }
        if (($stats['pet']['count'] ?? 0) >= 1) {
            $this->unlockAchievement('first_petting');
        }
        if (($stats['play']['count'] ?? 0) >= 1) {
            $this->unlockAchievement('first_play');
        }

        // Cat whisperer - wisdom from 3 cats
        if (count($stats['wisdom']['cats'] ?? []) >= 3) {
            $this->unlockAchievement('cat_whisperer');
        }

        // Therapy champion - 5 therapy sessions
        if (($stats['therapy']['count'] ?? 0) >= 5) {
            $this->unlockAchievement('therapy_champion');
        }

        // Social butterfly - interact with 5 different cats
        $allCats = array_merge(
            $stats['feed']['cats'] ?? [],
            $stats['pet']['cats'] ?? [],
            $stats['play']['cats'] ?? [],
            $stats['rest']['cats'] ?? []
        );
        if (count(array_unique($allCats)) >= 5) {
            $this->unlockAchievement('social_butterfly');
        }

        // Dedicated visitor - 20 total interactions
        $totalInteractions = ($stats['feed']['count'] ?? 0) +
                            ($stats['pet']['count'] ?? 0) +
                            ($stats['play']['count'] ?? 0) +
                            ($stats['rest']['count'] ?? 0);
        if ($totalInteractions >= 20) {
            $this->unlockAchievement('dedicated_visitor');
        }
    }
}
