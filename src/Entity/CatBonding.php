<?php

namespace App\Entity;

use App\Repository\CatBondingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CatBondingRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_cat_bonding', columns: ['user_id', 'cat_id'])]
class CatBonding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Cat::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Cat $cat;

    #[ORM\Column]
    private int $bondingLevel = 0;

    #[ORM\Column(nullable: true)]
    private ?int $compatibilityScore = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Cat $cat)
    {
        $this->user = $user;
        $this->cat = $cat;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCat(): Cat
    {
        return $this->cat;
    }

    public function getBondingLevel(): int
    {
        return $this->bondingLevel;
    }

    public function setBondingLevel(int $level): static
    {
        $this->bondingLevel = max(0, min(100, $level));
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function increaseBonding(int $amount): static
    {
        return $this->setBondingLevel($this->bondingLevel + $amount);
    }

    public function getCompatibilityScore(): ?int
    {
        return $this->compatibilityScore;
    }

    public function setCompatibilityScore(?int $score): static
    {
        $this->compatibilityScore = $score !== null ? max(0, min(100, $score)) : null;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Bonding milestone methods (matching Cat entity's methods)
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
        if ($this->bondingLevel >= 80) return "\u{1F495}"; // 💕
        if ($this->bondingLevel >= 50) return "\u{2764}\u{FE0F}"; // ❤️
        if ($this->bondingLevel >= 30) return "\u{1F9E1}"; // 🧡
        if ($this->bondingLevel >= 10) return "\u{1F49B}"; // 💛
        return "\u{1F90D}"; // 🤍
    }

    public function canFoster(): bool
    {
        return $this->bondingLevel >= 30 && $this->compatibilityScore !== null;
    }

    public function canAdopt(): bool
    {
        return $this->bondingLevel >= 50 && $this->cat->isFostered() && $this->cat->isOwnedBy($this->user);
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
        if ($this->compatibilityScore === null) return "\u{2753}"; // ❓
        if ($this->compatibilityScore >= 80) return "\u{1F31F}"; // 🌟
        if ($this->compatibilityScore >= 60) return "\u{2B50}"; // ⭐
        if ($this->compatibilityScore >= 40) return "\u{1F44D}"; // 👍
        return "\u{1F914}"; // 🤔
    }
}
