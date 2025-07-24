<?php

namespace App\Repository;

use App\Entity\Student;
use Doctrine\Persistence\ManagerRegistry;

class StudentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

    public function findByCinOrName(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.cin LIKE :query')
            ->orWhere('s.firstName LIKE :query')
            ->orWhere('s.lastName LIKE :query')
            ->orWhere("CONCAT(s.firstName, ' ', s.lastName) LIKE :query")
            ->setParameter('query', "%$query%")
            ->andWhere('s.isActive = true')
            ->orderBy('s.lastName', 'ASC')
            ->addOrderBy('s.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByRegistrationPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.registrationDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
