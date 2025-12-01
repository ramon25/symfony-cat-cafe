<?php

namespace App\Twig\Components;

use App\Entity\Cat;
use App\Repository\CatRepository;
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

    public function __construct(
        private readonly CatRepository $catRepository,
        private readonly EntityManagerInterface $entityManager,
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
            $cat->feed();
            $this->entityManager->flush();
            $this->lastAction = 'feed';
            $this->actionMessage = "Yum! {$cat->getName()} enjoyed their meal! 🍽️";
        }
    }

    #[LiveAction]
    public function pet(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            $cat->pet();
            $this->entityManager->flush();
            $this->lastAction = 'pet';
            $this->actionMessage = "{$cat->getName()} purrs contentedly... 🤗";
        }
    }

    #[LiveAction]
    public function play(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            $cat->play();
            $this->entityManager->flush();
            $this->lastAction = 'play';
            $this->actionMessage = "{$cat->getName()} had so much fun playing! 🧶";
        }
    }

    #[LiveAction]
    public function rest(): void
    {
        $cat = $this->getCat();
        if ($cat && !$cat->isAdopted()) {
            $cat->rest();
            $this->entityManager->flush();
            $this->lastAction = 'rest';
            $this->actionMessage = "{$cat->getName()} is feeling refreshed! 😴";
        }
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
