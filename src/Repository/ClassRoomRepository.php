<?php

namespace App\Repository;

use App\Entity\ClassRoom;
use Doctrine\Persistence\ManagerRegistry;

class ClassRoomRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassRoom::class);
    }

    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.formation = :formationId')
            ->andWhere('c.isActive = true')
            ->setParameter('formationId', $formationId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicLevel(int $academicLevelId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.academicLevel = :academicLevelId')
            ->andWhere('c.isActive = true')
            ->setParameter('academicLevelId', $academicLevelId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableClasses(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.currentStudents < c.capacity')
            ->andWhere('c.isActive = true')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getClassOccupancyStats(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.name', 'c.capacity', 'c.currentStudents',
                '(c.currentStudents * 100.0 / c.capacity) as occupancy_rate')
            ->where('c.isActive = true')
            ->orderBy('occupancy_rate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
