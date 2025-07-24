<?php

namespace App\Repository;

use App\Entity\StatisticsCache;
use Doctrine\Persistence\ManagerRegistry;

class StatisticsCacheRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatisticsCache::class);
    }

    public function findByStatType(string $statType): ?StatisticsCache
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.statType = :statType')
            ->andWhere('sc.expiresAt > :now')
            ->setParameter('statType', $statType)
            ->setParameter('now', new \DateTime())
            ->orderBy('sc.calculatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredCache(): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function cleanExpiredCache(): int
    {
        return $this->createQueryBuilder('sc')
            ->delete()
            ->where('sc.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
