<?php

namespace App\MessageHandler;

use App\Message\CatMovementMessage;
use App\Repository\CatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles cat movements - makes cats randomly arrive at or leave the cafe.
 * This simulates cats having their own schedules and wandering around.
 */
#[AsMessageHandler]
final class CatMovementHandler
{
    public function __construct(
        private readonly CatRepository $catRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(CatMovementMessage $message): void
    {
        $arrivals = 0;
        $departures = 0;

        // Get all non-adopted cats
        $cats = $this->catRepository->findAvailable();

        foreach ($cats as $cat) {
            if ($cat->isInCafe()) {
                // Cat is at the cafe - might leave
                if ($this->shouldMove($message->leaveChance)) {
                    $cat->leaveCafe();
                    $departures++;
                    $this->logger?->info('Cat {name} left the cafe for an adventure', [
                        'name' => $cat->getName(),
                        'cat_id' => $cat->getId(),
                    ]);
                }
            } else {
                // Cat is away - might return
                if ($this->shouldMove($message->returnChance)) {
                    $cat->arriveAtCafe();
                    $arrivals++;
                    $this->logger?->info('Cat {name} returned to the cafe', [
                        'name' => $cat->getName(),
                        'cat_id' => $cat->getId(),
                    ]);
                }
            }
        }

        $this->entityManager->flush();

        $this->logger?->info('Cat movement update: {arrivals} arrivals, {departures} departures', [
            'arrivals' => $arrivals,
            'departures' => $departures,
            'leave_chance' => $message->leaveChance,
            'return_chance' => $message->returnChance,
        ]);
    }

    /**
     * Determine if a movement should occur based on the given chance percentage.
     */
    private function shouldMove(int $chancePercent): bool
    {
        return random_int(1, 100) <= $chancePercent;
    }
}
