<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserSession;
use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SecurityService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    public function validateIpAccess(User $user, string $clientIp): bool
    {
        if (!$user->getAllowedIpAddress()) {
            return true; // Pas de restriction IP
        }

        return $user->getAllowedIpAddress() === $clientIp;
    }

    public function createSession(User $user, string $ipAddress): UserSession
    {
        // Désactiver les anciennes sessions
        $this->deactivateUserSessions($user);

        $session = new UserSession();
        $session->setUser($user);
        $session->setIpAddress($ipAddress);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        // Log de connexion
        $this->logAction($user, AuditLog::ACTION_LOGIN, 'users', $user->getId());

        return $session;
    }

    public function validateSession(string $sessionToken): ?UserSession
    {
        $session = $this->entityManager->getRepository(UserSession::class)
            ->findOneBy([
                'sessionToken' => $sessionToken,
                'isActive' => true
            ]);

        if (!$session || $session->isExpired()) {
            return null;
        }

        // Mettre à jour l'activité
        $session->updateActivity();
        $this->entityManager->flush();

        return $session;
    }

    public function deactivateUserSessions(User $user): void
    {
        $this->entityManager->getRepository(UserSession::class)
            ->createQueryBuilder('us')
            ->update()
            ->set('us.isActive', 'false')
            ->where('us.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function cleanExpiredSessions(): int
    {
        return $this->entityManager->getRepository(UserSession::class)
            ->createQueryBuilder('us')
            ->delete()
            ->where('us.expiresAt < :now')
            ->orWhere('us.isActive = false')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function logAction(
        ?User $user,
        string $action,
        string $tableName,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        $log = AuditLog::create(
            $user,
            $action,
            $tableName,
            $recordId,
            $oldValues,
            $newValues,
            $request?->getClientIp(),
            $request?->headers->get('User-Agent')
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
