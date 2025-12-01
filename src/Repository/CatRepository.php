<?php

namespace App\Repository;

use App\Entity\Cat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cat>
 */
class CatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cat::class);
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.adopted = :adopted')
            ->setParameter('adopted', false)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAdopted(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.adopted = :adopted')
            ->setParameter('adopted', true)
            ->orderBy('c.adoptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAvailable(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.adopted = :adopted')
            ->setParameter('adopted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAdopted(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.adopted = :adopted')
            ->setParameter('adopted', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findHungryCats(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.adopted = :adopted')
            ->andWhere('c.hunger > :hunger')
            ->setParameter('adopted', false)
            ->setParameter('hunger', 70)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find cats owned by a specific user
     *
     * @return Cat[]
     */
    public function findByOwner(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('c.adoptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find adopted cats for a specific user
     *
     * @return Cat[]
     */
    public function findAdoptedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.owner = :owner')
            ->andWhere('c.adopted = :adopted')
            ->setParameter('owner', $user)
            ->setParameter('adopted', true)
            ->orderBy('c.adoptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find fostered (but not yet adopted) cats for a specific user
     *
     * @return Cat[]
     */
    public function findFosteredByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.owner = :owner')
            ->andWhere('c.fostered = :fostered')
            ->andWhere('c.adopted = :adopted')
            ->setParameter('owner', $user)
            ->setParameter('fostered', true)
            ->setParameter('adopted', false)
            ->orderBy('c.fosteredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
