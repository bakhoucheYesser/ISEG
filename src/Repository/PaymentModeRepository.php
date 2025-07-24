<?php

namespace App\Repository;

use App\Entity\PaymentMode;
use Doctrine\Persistence\ManagerRegistry;

class PaymentModeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMode::class);
    }

    public function findByCode(string $code): ?PaymentMode
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrderedByFrequency(): array
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.isActive = true')
            ->orderBy('pm.frequencyMonths', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getPaymentModeStats(): array
    {
        return $this->createQueryBuilder('pm')
            ->select('pm.name', 'pm.code', 'COUNT(e.id) as usage_count')
            ->leftJoin('pm.enrollments', 'e')
            ->where('pm.isActive = true')
            ->groupBy('pm.id', 'pm.name', 'pm.code')
            ->orderBy('usage_count', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
