<?php

namespace App\Repository;

use App\Entity\AcademicLevel;
use Doctrine\Persistence\ManagerRegistry;

class AcademicLevelRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcademicLevel::class);
    }

    public function findActiveByCode(): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.isActive = true')
            ->orderBy('al.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?AcademicLevel
    {
        return $this->createQueryBuilder('al')
            ->where('al.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCode(string $code): ?AcademicLevel
    {
        return $this->createQueryBuilder('al')
            ->where('al.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
