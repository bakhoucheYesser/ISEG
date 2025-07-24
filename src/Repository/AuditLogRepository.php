<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByAction(string $action, int $limit = 50): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.action = :action')
            ->setParameter('action', $action)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSuspiciousActivities(): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.severity IN (:severities)')
            ->setParameter('severities', [AuditLog::SEVERITY_HIGH, AuditLog::SEVERITY_CRITICAL])
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
