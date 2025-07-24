<?php

// src/Repository/PaymentInstallmentRepository.php
namespace App\Repository;

use App\Entity\PaymentInstallment;
use Doctrine\Persistence\ManagerRegistry;

class PaymentInstallmentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentInstallment::class);
    }

    public function findOverdueInstallments(): array
    {
        return $this->createQueryBuilder('pi')
            ->where('pi.status = :pending')
            ->andWhere('pi.dueDate < :today')
            ->setParameter('pending', 'PENDING')
            ->setParameter('today', new \DateTime())
            ->orderBy('pi.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEnrollment(int $enrollmentId): array
    {
        return $this->createQueryBuilder('pi')
            ->where('pi.enrollment = :enrollmentId')
            ->setParameter('enrollmentId', $enrollmentId)
            ->orderBy('pi.installmentNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
