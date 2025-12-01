<?php

namespace App\MessageHandler;

use App\Message\UpdateCatStatsMessage;
use App\Repository\CatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateCatStatsHandler
{
    public function __construct(
        private readonly CatRepository $catRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(UpdateCatStatsMessage $message): void
    {
        // Only update cats that are currently at the cafe
        $cats = $this->catRepository->findInCafe();
        $updatedCount = 0;

        foreach ($cats as $cat) {
            // Increase hunger (cats get hungry over time)
            $newHunger = min(100, $cat->getHunger() + $message->hungerIncrease);
            $cat->setHunger($newHunger);

            // Decrease energy (cats get tired over time)
            $newEnergy = max(0, $cat->getEnergy() - $message->energyDecrease);
            $cat->setEnergy($newEnergy);

            // Happiness slowly decreases if hungry or tired
            if ($cat->getHunger() > 70 || $cat->getEnergy() < 30) {
                $newHappiness = max(0, $cat->getHappiness() - 2);
                $cat->setHappiness($newHappiness);
            }

            $updatedCount++;
        }

        $this->entityManager->flush();

        $this->logger?->info('Updated stats for {count} cats in cafe', [
            'count' => $updatedCount,
            'hunger_increase' => $message->hungerIncrease,
            'energy_decrease' => $message->energyDecrease,
        ]);
    }
}
