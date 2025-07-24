<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function findByAcademicLevel(int $academicLevelId): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.academicLevel = :academicLevelId')
            ->andWhere('f.isActive = true')
            ->setParameter('academicLevelId', $academicLevelId)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?Formation
    {
        return $this->createQueryBuilder('f')
            ->where('f.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getActiveFormationsWithStats(): array
    {
        return $this->createQueryBuilder('f')
            ->select('f, COUNT(e.id) as enrollment_count')
            ->leftJoin('f.enrollments', 'e')
            ->where('f.isActive = true')
            ->groupBy('f.id')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
