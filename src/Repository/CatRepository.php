<?php

namespace App\Repository;

use App\Entity\Cat;
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
}
