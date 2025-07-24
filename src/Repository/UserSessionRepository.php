<?php

namespace App\Repository;

use App\Entity\UserSession;
use Doctrine\Persistence\ManagerRegistry;

class UserSessionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }

    public function findByToken(string $token): ?UserSession
    {
        return $this->createQueryBuilder('us')
            ->where('us.sessionToken = :token')
            ->andWhere('us.isActive = true')
            ->andWhere('us.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveSessionsForUser(int $userId): array
    {
        return $this->createQueryBuilder('us')
            ->where('us.user = :userId')
            ->andWhere('us.isActive = true')
            ->andWhere('us.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('us.lastActivity', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function cleanExpiredSessions(): int
    {
        return $this->createQueryBuilder('us')
            ->delete()
            ->where('us.expiresAt < :now')
            ->orWhere('us.isActive = false')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
