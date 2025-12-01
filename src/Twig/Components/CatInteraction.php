<?php

namespace App\Twig\Components;

use App\Entity\Cat;
use App\Repository\CatRepository;
use App\Service\AchievementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('CatInteraction')]
class CatInteraction
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $catId;

    #[LiveProp(writable: true)]
    public string $lastAction = '';

    #[LiveProp(writable: true)]
    public string $actionMessage = '';

    #[LiveProp(writable: true)]
    public string $bondingMessage = '';

    public function __construct(
        private readonly CatRepository $catRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AchievementService $achievementService,
    ) {
    }

    public function getCat(): ?Cat
    {
        return $this->catRepository->find($this->catId);
    }

    #[LiveAction]
    public function feed(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            if (!$cat->canFeed()) {
                $this->lastAction = 'error';
                $this->actionMessage = "{$cat->getName()} is already well-fed and content!";
                $this->bondingMessage = '';
                return;
            }
            $cat->feed();
            $this->entityManager->flush();
            $this->achievementService->incrementStat('feed', $cat->getId());
            $this->checkBondingAchievements($cat);
            $this->lastAction = 'feed';
            $bonus = $cat->getPreferredInteraction() === Cat::INTERACTION_FEED ? ' 💕 They LOVE this!' : '';
            $this->actionMessage = "Yum! {$cat->getName()} enjoyed their meal! 🍽️{$bonus}";
            $this->bondingMessage = $this->getBondingMessage($cat, Cat::INTERACTION_FEED);
        }
    }

    #[LiveAction]
    public function pet(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            if (!$cat->canPet()) {
                $this->lastAction = 'error';
                $this->actionMessage = "{$cat->getName()} is already at maximum happiness and too tired for pets!";
                $this->bondingMessage = '';
                return;
            }
            $cat->pet();
            $this->entityManager->flush();
            $this->achievementService->incrementStat('pet', $cat->getId());
            $this->checkBondingAchievements($cat);
            $this->lastAction = 'pet';
            $bonus = $cat->getPreferredInteraction() === Cat::INTERACTION_PET ? ' 💕 They LOVE this!' : '';
            $this->actionMessage = "{$cat->getName()} purrs contentedly... 🤗{$bonus}";
            $this->bondingMessage = $this->getBondingMessage($cat, Cat::INTERACTION_PET);
        }
    }

    #[LiveAction]
    public function play(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            if (!$cat->canPlay()) {
                $this->lastAction = 'error';
                $this->actionMessage = "{$cat->getName()} is already at maximum happiness, too tired, and too full to play!";
                $this->bondingMessage = '';
                return;
            }
            $cat->play();
            $this->entityManager->flush();
            $this->achievementService->incrementStat('play', $cat->getId());
            $this->checkBondingAchievements($cat);
            $this->lastAction = 'play';
            $bonus = $cat->getPreferredInteraction() === Cat::INTERACTION_PLAY ? ' 💕 They LOVE this!' : '';
            $this->actionMessage = "{$cat->getName()} had so much fun playing! 🧶{$bonus}";
            $this->bondingMessage = $this->getBondingMessage($cat, Cat::INTERACTION_PLAY);
        }
    }

    #[LiveAction]
    public function rest(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            if (!$cat->canRest()) {
                $this->lastAction = 'error';
                $this->actionMessage = "{$cat->getName()} already has full energy and is too full to rest!";
                $this->bondingMessage = '';
                return;
            }
            $cat->rest();
            $this->entityManager->flush();
            $this->achievementService->incrementStat('rest', $cat->getId());
            $this->checkBondingAchievements($cat);
            $this->lastAction = 'rest';
            $bonus = $cat->getPreferredInteraction() === Cat::INTERACTION_REST ? ' 💕 They LOVE this!' : '';
            $this->actionMessage = "{$cat->getName()} is feeling refreshed! 😴{$bonus}";
            $this->bondingMessage = $this->getBondingMessage($cat, Cat::INTERACTION_REST);
        }
    }

    private function checkBondingAchievements(Cat $cat): void
    {
        if ($cat->getBondingLevel() >= 80) {
            $this->achievementService->unlockAchievement('best_friends');
        }
    }

    private function getBondingMessage(Cat $cat, string $action): string
    {
        $isPreferred = $cat->getPreferredInteraction() === $action;
        $points = $isPreferred ? 10 : 5;
        return "+{$points} bonding " . ($isPreferred ? '(favorite!)' : '');
    }

    #[LiveAction]
    public function clearMessage(): void
    {
        $this->lastAction = '';
        $this->actionMessage = '';
        $this->bondingMessage = '';
    }

    public function getHungerBarColor(): string
    {
        $cat = $this->getCat();
        if (!$cat) return 'from-red-300 to-red-500';

        return $cat->getHunger() > 70
            ? 'from-red-400 to-red-600'
            : 'from-red-300 to-red-500';
    }

    public function getHappinessBarColor(): string
    {
        $cat = $this->getCat();
        if (!$cat) return 'from-green-300 to-green-500';

        return $cat->getHappiness() < 30
            ? 'from-yellow-400 to-orange-500'
            : 'from-green-300 to-green-500';
    }

    public function getEnergyBarColor(): string
    {
        $cat = $this->getCat();
        if (!$cat) return 'from-blue-300 to-blue-500';

        return $cat->getEnergy() < 20
            ? 'from-gray-400 to-gray-500'
            : 'from-blue-300 to-blue-500';
    }
}
