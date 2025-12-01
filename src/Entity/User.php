<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+$/', message: 'Username can only contain letters, numbers, and underscores')]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Cat>
     */
    #[ORM\OneToMany(targetEntity: Cat::class, mappedBy: 'owner')]
    private Collection $cats;

    /**
     * @var Collection<int, UserAchievement>
     */
    #[ORM\OneToMany(targetEntity: UserAchievement::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $achievements;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $achievementStats = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->cats = new ArrayCollection();
        $this->achievements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Cat>
     */
    public function getCats(): Collection
    {
        return $this->cats;
    }

    public function addCat(Cat $cat): static
    {
        if (!$this->cats->contains($cat)) {
            $this->cats->add($cat);
            $cat->setOwner($this);
        }
        return $this;
    }

    public function removeCat(Cat $cat): static
    {
        if ($this->cats->removeElement($cat)) {
            // set the owning side to null (unless already changed)
            if ($cat->getOwner() === $this) {
                $cat->setOwner(null);
            }
        }
        return $this;
    }

    public function getAdoptedCats(): Collection
    {
        return $this->cats->filter(fn(Cat $cat) => $cat->isAdopted());
    }

    public function getFosteredCats(): Collection
    {
        return $this->cats->filter(fn(Cat $cat) => $cat->isFostered() && !$cat->isAdopted());
    }

    /**
     * @return Collection<int, UserAchievement>
     */
    public function getAchievements(): Collection
    {
        return $this->achievements;
    }

    public function addAchievement(UserAchievement $achievement): static
    {
        if (!$this->achievements->contains($achievement)) {
            $this->achievements->add($achievement);
            $achievement->setUser($this);
        }
        return $this;
    }

    public function removeAchievement(UserAchievement $achievement): static
    {
        if ($this->achievements->removeElement($achievement)) {
            // set the owning side to null (unless already changed)
            if ($achievement->getUser() === $this) {
                $achievement->setUser(null);
            }
        }
        return $this;
    }

    public function hasAchievement(string $achievementId): bool
    {
        foreach ($this->achievements as $achievement) {
            if ($achievement->getAchievementId() === $achievementId) {
                return true;
            }
        }
        return false;
    }

    public function getAchievementStats(): ?array
    {
        return $this->achievementStats;
    }

    public function setAchievementStats(?array $achievementStats): static
    {
        $this->achievementStats = $achievementStats;
        return $this;
    }

    public function getTotalAchievementPoints(): int
    {
        $points = 0;
        foreach ($this->achievements as $achievement) {
            $points += $achievement->getPoints();
        }
        return $points;
    }
}
