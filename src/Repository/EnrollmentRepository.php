<?php

namespace App\Repository;

use App\Entity\Enrollment;
use Doctrine\Persistence\ManagerRegistry;

class EnrollmentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function findByPaymentStatus(string $status): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.student', 's')
            ->where('e.paymentStatus = :status')
            ->andWhere('e.isActive = true')
            ->setParameter('status', $status)
            ->orderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicLevel(string $levelCode): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.formation', 'f')
            ->join('f.academicLevel', 'al')
            ->where('al.code = :levelCode')
            ->andWhere('e.isActive = true')
            ->setParameter('levelCode', $levelCode)
            ->orderBy('e.enrollmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByClassRoom(int $classRoomId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.student', 's')
            ->where('e.classRoom = :classRoomId')
            ->andWhere('e.isActive = true')
            ->setParameter('classRoomId', $classRoomId)
            ->orderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getPaymentStatisticsByPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.paymentStatus, COUNT(e.id) as count, SUM(e.totalAmount) as totalAmount, SUM(e.totalPaid) as totalPaid')
            ->where('e.enrollmentDate BETWEEN :start AND :end')
            ->andWhere('e.isActive = true')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('e.paymentStatus')
            ->getQuery()
            ->getResult();
    }
}
