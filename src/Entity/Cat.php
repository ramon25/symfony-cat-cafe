<?php

namespace App\Entity;

use App\Repository\CatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CatRepository::class)]
class Cat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $breed = null;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 30)]
    private ?int $age = null;

    #[ORM\Column(length: 50)]
    private ?string $color = null;

    #[ORM\Column(length: 20)]
    private string $mood = 'content';

    #[ORM\Column]
    private int $hunger = 50;

    #[ORM\Column]
    private int $happiness = 50;

    #[ORM\Column]
    private int $energy = 50;

    #[ORM\Column]
    private bool $adopted = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $adoptedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(string $breed): static
    {
        $this->breed = $breed;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getMood(): string
    {
        return $this->mood;
    }

    public function setMood(string $mood): static
    {
        $this->mood = $mood;
        return $this;
    }

    public function getHunger(): int
    {
        return $this->hunger;
    }

    public function setHunger(int $hunger): static
    {
        $this->hunger = max(0, min(100, $hunger));
        $this->updateMood();
        return $this;
    }

    public function getHappiness(): int
    {
        return $this->happiness;
    }

    public function setHappiness(int $happiness): static
    {
        $this->happiness = max(0, min(100, $happiness));
        $this->updateMood();
        return $this;
    }

    public function getEnergy(): int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): static
    {
        $this->energy = max(0, min(100, $energy));
        $this->updateMood();
        return $this;
    }

    public function isAdopted(): bool
    {
        return $this->adopted;
    }

    public function setAdopted(bool $adopted): static
    {
        $this->adopted = $adopted;
        if ($adopted) {
            $this->adoptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAdoptedAt(): ?\DateTimeImmutable
    {
        return $this->adoptedAt;
    }

    public function feed(): void
    {
        $this->setHunger($this->hunger - 30);
        $this->setHappiness($this->happiness + 10);
        $this->setEnergy($this->energy + 5);
    }

    public function pet(): void
    {
        $this->setHappiness($this->happiness + 20);
        $this->setEnergy($this->energy - 5);
    }

    public function play(): void
    {
        $this->setHappiness($this->happiness + 25);
        $this->setEnergy($this->energy - 20);
        $this->setHunger($this->hunger + 10);
    }

    public function rest(): void
    {
        $this->setEnergy($this->energy + 30);
        $this->setHunger($this->hunger + 5);
    }

    private function updateMood(): void
    {
        $avgStat = ($this->happiness + (100 - $this->hunger) + $this->energy) / 3;

        if ($this->hunger > 80) {
            $this->mood = 'hungry';
        } elseif ($this->energy < 20) {
            $this->mood = 'sleepy';
        } elseif ($avgStat >= 75) {
            $this->mood = 'happy';
        } elseif ($avgStat >= 50) {
            $this->mood = 'content';
        } elseif ($avgStat >= 25) {
            $this->mood = 'grumpy';
        } else {
            $this->mood = 'upset';
        }
    }

    public function getMoodEmoji(): string
    {
        return match ($this->mood) {
            'happy' => '😸',
            'content' => '🐱',
            'grumpy' => '😾',
            'upset' => '😿',
            'hungry' => '🍽️',
            'sleepy' => '😴',
            default => '🐱',
        };
    }
}
