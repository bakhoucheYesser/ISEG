<?php

namespace App\Repository;

use App\Entity\Receipt;
use Doctrine\Persistence\ManagerRegistry;

class ReceiptRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Receipt::class);
    }

    public function findByReceiptNumber(string $receiptNumber): ?Receipt
    {
        return $this->createQueryBuilder('r')
            ->where('r.receiptNumber = :receiptNumber')
            ->setParameter('receiptNumber', $receiptNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentReceipts(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.generatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUnprintedReceipts(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.printedCount = 0')
            ->orderBy('r.generatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
