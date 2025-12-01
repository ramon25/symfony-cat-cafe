<?php

namespace App\Repository;

use App\Entity\Cat;
use App\Entity\CatBonding;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CatBonding>
 */
class CatBondingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatBonding::class);
    }

    /**
     * Find bonding record for a specific user and cat.
     * Returns null if the user has never interacted with this cat.
     */
    public function findByUserAndCat(User $user, Cat $cat): ?CatBonding
    {
        return $this->createQueryBuilder('cb')
            ->andWhere('cb.user = :user')
            ->andWhere('cb.cat = :cat')
            ->setParameter('user', $user)
            ->setParameter('cat', $cat)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get or create a bonding record for a user and cat.
     * Creates a new record if one doesn't exist.
     */
    public function getOrCreate(User $user, Cat $cat): CatBonding
    {
        $bonding = $this->findByUserAndCat($user, $cat);

        if ($bonding === null) {
            $bonding = new CatBonding($user, $cat);
            $this->getEntityManager()->persist($bonding);
        }

        return $bonding;
    }

    /**
     * Find all bonding records for a specific user.
     *
     * @return CatBonding[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('cb')
            ->andWhere('cb.user = :user')
            ->setParameter('user', $user)
            ->orderBy('cb.bondingLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all bonding records for a specific cat.
     *
     * @return CatBonding[]
     */
    public function findByCat(Cat $cat): array
    {
        return $this->createQueryBuilder('cb')
            ->andWhere('cb.cat = :cat')
            ->setParameter('cat', $cat)
            ->orderBy('cb.bondingLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get bonding level for a user and cat.
     * Returns 0 if no bonding record exists.
     */
    public function getBondingLevel(User $user, Cat $cat): int
    {
        $bonding = $this->findByUserAndCat($user, $cat);
        return $bonding?->getBondingLevel() ?? 0;
    }

    /**
     * Get compatibility score for a user and cat.
     * Returns null if no quiz has been completed.
     */
    public function getCompatibilityScore(User $user, Cat $cat): ?int
    {
        $bonding = $this->findByUserAndCat($user, $cat);
        return $bonding?->getCompatibilityScore();
    }

    /**
     * Find cats where user has reached the fostering threshold (30%+ bonding).
     *
     * @return CatBonding[]
     */
    public function findFosterReadyByUser(User $user): array
    {
        return $this->createQueryBuilder('cb')
            ->join('cb.cat', 'c')
            ->andWhere('cb.user = :user')
            ->andWhere('cb.bondingLevel >= :minLevel')
            ->andWhere('cb.compatibilityScore IS NOT NULL')
            ->andWhere('c.adopted = :adopted')
            ->andWhere('c.fostered = :fostered')
            ->setParameter('user', $user)
            ->setParameter('minLevel', 30)
            ->setParameter('adopted', false)
            ->setParameter('fostered', false)
            ->orderBy('cb.bondingLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the user with the highest bonding level for a specific cat.
     * Useful for showing "top fan" or similar features.
     */
    public function findTopBonderForCat(Cat $cat): ?CatBonding
    {
        return $this->createQueryBuilder('cb')
            ->andWhere('cb.cat = :cat')
            ->setParameter('cat', $cat)
            ->orderBy('cb.bondingLevel', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all cats a user has bonded with (any level > 0).
     *
     * @return CatBonding[]
     */
    public function findBondedCatsByUser(User $user): array
    {
        return $this->createQueryBuilder('cb')
            ->join('cb.cat', 'c')
            ->andWhere('cb.user = :user')
            ->andWhere('cb.bondingLevel > 0')
            ->setParameter('user', $user)
            ->orderBy('cb.bondingLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total bonding points across all cats for a user.
     * Useful for leaderboards.
     */
    public function getTotalBondingPointsByUser(User $user): int
    {
        $result = $this->createQueryBuilder('cb')
            ->select('SUM(cb.bondingLevel)')
            ->andWhere('cb.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
