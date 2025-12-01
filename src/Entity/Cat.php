<?php

namespace App\Entity;

use App\Repository\CatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CatRepository::class)]
class Cat
{
    // User ownership - the user who adopted/fostered this cat
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cats')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;
    public const INTERACTION_FEED = 'feed';
    public const INTERACTION_PET = 'pet';
    public const INTERACTION_PLAY = 'play';
    public const INTERACTION_REST = 'rest';

    public const ALL_INTERACTIONS = [
        self::INTERACTION_FEED,
        self::INTERACTION_PET,
        self::INTERACTION_PLAY,
        self::INTERACTION_REST,
    ];

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

    // Bonding System
    #[ORM\Column]
    private int $bondingLevel = 0;

    // Cat Preferences System - which interaction this cat loves most
    #[ORM\Column(length: 20)]
    private string $preferredInteraction = 'pet';

    // Fostering System
    #[ORM\Column]
    private bool $fostered = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $fosteredAt = null;

    // Compatibility quiz score (stored when user completes quiz)
    #[ORM\Column(nullable: true)]
    private ?int $compatibilityScore = null;

    // AI-generated content (cached in database)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiPersonalityProfile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiBackstory = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $aiFunFacts = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $aiGeneratedAt = null;

    // Cafe presence tracking - whether the cat is currently at the cafe
    #[ORM\Column]
    private bool $inCafe = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastVisitAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $leftCafeAt = null;

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
        // Note: Bonding is now tracked per-user via CatBonding entity
    }

    /**
     * Check if feeding would have any effect on the cat's stats.
     * Feed decreases hunger by 30, increases happiness by 10, increases energy by 5.
     */
    public function canFeed(): bool
    {
        // Can feed if any stat would change
        return $this->hunger > 0 || $this->happiness < 100 || $this->energy < 100;
    }

    public function pet(): void
    {
        $this->setHappiness($this->happiness + 20);
        $this->setEnergy($this->energy - 5);
        // Note: Bonding is now tracked per-user via CatBonding entity
    }

    /**
     * Check if petting would have any effect on the cat's stats.
     * Pet increases happiness by 20, decreases energy by 5.
     */
    public function canPet(): bool
    {
        // Can pet if any stat would change
        return $this->happiness < 100 || $this->energy > 0;
    }

    public function play(): void
    {
        $this->setHappiness($this->happiness + 25);
        $this->setEnergy($this->energy - 20);
        $this->setHunger($this->hunger + 10);
        // Note: Bonding is now tracked per-user via CatBonding entity
    }

    /**
     * Check if playing would have any effect on the cat's stats.
     * Play increases happiness by 25, decreases energy by 20, increases hunger by 10.
     */
    public function canPlay(): bool
    {
        // Can play if any stat would change
        return $this->happiness < 100 || $this->energy > 0 || $this->hunger < 100;
    }

    public function rest(): void
    {
        $this->setEnergy($this->energy + 30);
        $this->setHunger($this->hunger + 5);
        // Note: Bonding is now tracked per-user via CatBonding entity
    }

    /**
     * Check if resting would have any effect on the cat's stats.
     * Rest increases energy by 30, increases hunger by 5.
     */
    public function canRest(): bool
    {
        // Can rest if any stat would change
        return $this->energy < 100 || $this->hunger < 100;
    }

    // Bonding System Methods

    /**
     * Calculate the bonding increase amount for a given interaction type.
     * Returns 10 for preferred interactions, 5 for others.
     */
    public function calculateBondingIncrease(string $interactionType): int
    {
        // Bonus if this is the cat's preferred interaction
        if ($interactionType === $this->preferredInteraction) {
            return 10; // Double bonding for preferred interaction!
        }
        return 5;
    }

    /**
     * @deprecated Use CatBonding entity for user-specific bonding levels
     */
    public function getBondingLevel(): int
    {
        return $this->bondingLevel;
    }

    public function setBondingLevel(int $level): static
    {
        $this->bondingLevel = max(0, min(100, $level));
        return $this;
    }

    public function increaseBonding(string $interactionType): void
    {
        // Base bonding increase
        $baseIncrease = 5;

        // Bonus if this is the cat's preferred interaction
        if ($interactionType === $this->preferredInteraction) {
            $baseIncrease = 10; // Double bonding for preferred interaction!
        }

        $this->setBondingLevel($this->bondingLevel + $baseIncrease);
    }

    public function canBeAdopted(): bool
    {
        // Must have 50% bonding AND be fostered to adopt
        return $this->bondingLevel >= 50 && $this->fostered;
    }

    public function canBeFostered(): bool
    {
        // Must have 30% bonding AND completed compatibility quiz to foster
        return $this->bondingLevel >= 30 && $this->compatibilityScore !== null;
    }

    public function getBondingMilestone(): string
    {
        if ($this->bondingLevel >= 80) return 'Best Friends';
        if ($this->bondingLevel >= 50) return 'Close Bond';
        if ($this->bondingLevel >= 30) return 'Getting Closer';
        if ($this->bondingLevel >= 10) return 'Acquaintances';
        return 'Just Met';
    }

    public function getBondingEmoji(): string
    {
        if ($this->bondingLevel >= 80) return '💕';
        if ($this->bondingLevel >= 50) return '❤️';
        if ($this->bondingLevel >= 30) return '🧡';
        if ($this->bondingLevel >= 10) return '💛';
        return '🤍';
    }

    // Cat Preferences System Methods
    public function getPreferredInteraction(): string
    {
        return $this->preferredInteraction;
    }

    public function setPreferredInteraction(string $interaction): static
    {
        $this->preferredInteraction = $interaction;
        return $this;
    }

    public function getPreferredInteractionEmoji(): string
    {
        return match ($this->preferredInteraction) {
            self::INTERACTION_FEED => '🍽️',
            self::INTERACTION_PET => '🤗',
            self::INTERACTION_PLAY => '🧶',
            self::INTERACTION_REST => '😴',
            default => '❓',
        };
    }

    public function getPreferredInteractionLabel(): string
    {
        return match ($this->preferredInteraction) {
            self::INTERACTION_FEED => 'Being fed',
            self::INTERACTION_PET => 'Being petted',
            self::INTERACTION_PLAY => 'Playing',
            self::INTERACTION_REST => 'Resting together',
            default => 'Unknown',
        };
    }

    // Fostering System Methods
    public function isFostered(): bool
    {
        return $this->fostered;
    }

    public function setFostered(bool $fostered): static
    {
        $this->fostered = $fostered;
        if ($fostered && $this->fosteredAt === null) {
            $this->fosteredAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getFosteredAt(): ?\DateTimeImmutable
    {
        return $this->fosteredAt;
    }

    // Compatibility Quiz Methods
    public function getCompatibilityScore(): ?int
    {
        return $this->compatibilityScore;
    }

    public function setCompatibilityScore(?int $score): static
    {
        $this->compatibilityScore = $score !== null ? max(0, min(100, $score)) : null;
        return $this;
    }

    public function getCompatibilityLabel(): string
    {
        if ($this->compatibilityScore === null) return 'Not tested';
        if ($this->compatibilityScore >= 80) return 'Perfect Match!';
        if ($this->compatibilityScore >= 60) return 'Great Match';
        if ($this->compatibilityScore >= 40) return 'Good Match';
        return 'Could Work';
    }

    public function getCompatibilityEmoji(): string
    {
        if ($this->compatibilityScore === null) return '❓';
        if ($this->compatibilityScore >= 80) return '🌟';
        if ($this->compatibilityScore >= 60) return '⭐';
        if ($this->compatibilityScore >= 40) return '👍';
        return '🤔';
    }

    // AI-Generated Content Methods
    public function getAiPersonalityProfile(): ?string
    {
        return $this->aiPersonalityProfile;
    }

    public function setAiPersonalityProfile(?string $profile): static
    {
        $this->aiPersonalityProfile = $profile;
        return $this;
    }

    public function getAiBackstory(): ?string
    {
        return $this->aiBackstory;
    }

    public function setAiBackstory(?string $backstory): static
    {
        $this->aiBackstory = $backstory;
        return $this;
    }

    public function getAiFunFacts(): ?array
    {
        return $this->aiFunFacts;
    }

    public function setAiFunFacts(?array $facts): static
    {
        $this->aiFunFacts = $facts;
        return $this;
    }

    public function getAiGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->aiGeneratedAt;
    }

    public function setAiGeneratedAt(?\DateTimeImmutable $generatedAt): static
    {
        $this->aiGeneratedAt = $generatedAt;
        return $this;
    }

    public function hasAiContent(): bool
    {
        return $this->aiPersonalityProfile !== null || $this->aiBackstory !== null;
    }

    public function clearAiContent(): static
    {
        $this->aiPersonalityProfile = null;
        $this->aiBackstory = null;
        $this->aiFunFacts = null;
        $this->aiGeneratedAt = null;
        return $this;
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

    // User Ownership Methods
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function hasOwner(): bool
    {
        return $this->owner !== null;
    }

    public function isOwnedBy(?User $user): bool
    {
        if ($user === null || $this->owner === null) {
            return false;
        }
        return $this->owner->getId() === $user->getId();
    }

    // Cafe Presence Methods
    public function isInCafe(): bool
    {
        return $this->inCafe;
    }

    public function setInCafe(bool $inCafe): static
    {
        $this->inCafe = $inCafe;
        return $this;
    }

    public function getLastVisitAt(): ?\DateTimeImmutable
    {
        return $this->lastVisitAt;
    }

    public function setLastVisitAt(?\DateTimeImmutable $lastVisitAt): static
    {
        $this->lastVisitAt = $lastVisitAt;
        return $this;
    }

    public function getLeftCafeAt(): ?\DateTimeImmutable
    {
        return $this->leftCafeAt;
    }

    public function setLeftCafeAt(?\DateTimeImmutable $leftCafeAt): static
    {
        $this->leftCafeAt = $leftCafeAt;
        return $this;
    }

    /**
     * Cat arrives at the cafe
     */
    public function arriveAtCafe(): static
    {
        $this->inCafe = true;
        $this->lastVisitAt = new \DateTimeImmutable();
        $this->leftCafeAt = null;
        return $this;
    }

    /**
     * Cat leaves the cafe
     */
    public function leaveCafe(): static
    {
        $this->inCafe = false;
        $this->leftCafeAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Get the cat's location status text
     */
    public function getLocationStatus(): string
    {
        if ($this->inCafe) {
            return 'At the cafe';
        }
        return 'Out exploring';
    }

    /**
     * Get emoji representing cat's location
     */
    public function getLocationEmoji(): string
    {
        return $this->inCafe ? '🏠' : '🌳';
    }
}
