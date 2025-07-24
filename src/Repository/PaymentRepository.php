<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paymentDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('p.paymentDate', 'DESC')
            ->addOrderBy('p.paymentTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnvalidatedPayments(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isValidated = false')
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalAmountByPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->where('p.paymentDate BETWEEN :start AND :end')
            ->andWhere('p.isValidated = true')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function findByStudent(int $studentId): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.enrollment', 'e')
            ->where('e.student = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('p.paymentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
