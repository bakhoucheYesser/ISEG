<?php

// src/Entity/UserSession.php (COMPLET)
namespace App\Entity;

use App\Repository\UserSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserSessionRepository::class)]
#[ORM\Table(name: 'user_sessions')]
class UserSession
{
    public const SESSION_DURATION_HOURS = 8;
    public const WARNING_THRESHOLD_MINUTES = 15; // Avertir 15min avant expiration

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $sessionToken = null;

    #[ORM\Column(length: 45)]
    #[Assert\Ip]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastActivity = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->lastActivity = new \DateTime();
        $this->sessionToken = bin2hex(random_bytes(32));
        // Session expire après 8 heures d'inactivité
        $this->expiresAt = (new \DateTime())->modify('+' . self::SESSION_DURATION_HOURS . ' hours');
    }

    // ====================================================
    // MÉTHODES MÉTIER
    // ====================================================

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function updateActivity(): void
    {
        $this->lastActivity = new \DateTime();
        // Étendre l'expiration de 8 heures
        $this->expiresAt = (new \DateTime())->modify('+' . self::SESSION_DURATION_HOURS . ' hours');
    }

    public function isValid(): bool
    {
        return $this->isActive && !$this->isExpired();
    }

    public function terminate(): void
    {
        $this->isActive = false;
        $this->expiresAt = new \DateTime(); // Expirer immédiatement
    }

    public function extend(int $hours = self::SESSION_DURATION_HOURS): void
    {
        if ($this->isActive && !$this->isExpired()) {
            $this->expiresAt = (new \DateTime())->modify("+{$hours} hours");
        }
    }

    public function getRemainingTime(): \DateInterval
    {
        $now = new \DateTime();

        if ($this->isExpired()) {
            return new \DateInterval('PT0S'); // 0 secondes
        }

        return $now->diff($this->expiresAt);
    }

    public function getRemainingTimeInMinutes(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        $interval = $this->getRemainingTime();
        return ($interval->h * 60) + $interval->i;
    }

    public function getRemainingTimeFormatted(): string
    {
        if ($this->isExpired()) {
            return 'Expiré';
        }

        $interval = $this->getRemainingTime();

        if ($interval->h > 0) {
            return sprintf('%dh %02dm', $interval->h, $interval->i);
        } elseif ($interval->i > 0) {
            return sprintf('%dm', $interval->i);
        } else {
            return 'Moins d\'1 minute';
        }
    }

    public function shouldShowWarning(): bool
    {
        if ($this->isExpired() || !$this->isActive) {
            return false;
        }

        return $this->getRemainingTimeInMinutes() <= self::WARNING_THRESHOLD_MINUTES;
    }

    public function getDuration(): \DateInterval
    {
        if (!$this->createdAt || !$this->lastActivity) {
            return new \DateInterval('PT0S');
        }

        return $this->createdAt->diff($this->lastActivity);
    }

    public function getDurationFormatted(): string
    {
        $interval = $this->getDuration();

        if ($interval->d > 0) {
            return sprintf('%d jour(s) %dh %02dm', $interval->d, $interval->h, $interval->i);
        } elseif ($interval->h > 0) {
            return sprintf('%dh %02dm', $interval->h, $interval->i);
        } elseif ($interval->i > 0) {
            return sprintf('%dm', $interval->i);
        } else {
            return 'Moins d\'1 minute';
        }
    }

    public function getTimeSinceLastActivity(): string
    {
        if (!$this->lastActivity) {
            return 'Inconnu';
        }

        $now = new \DateTime();
        $interval = $now->diff($this->lastActivity);

        if ($interval->d > 0) {
            return sprintf('%d jour(s)', $interval->d);
        } elseif ($interval->h > 0) {
            return sprintf('%dh %02dm', $interval->h, $interval->i);
        } elseif ($interval->i > 0) {
            return sprintf('%dm', $interval->i);
        } else {
            return 'À l\'instant';
        }
    }

    public function isRecentlyActive(): bool
    {
        if (!$this->lastActivity) {
            return false;
        }

        $fiveMinutesAgo = new \DateTime('-5 minutes');
        return $this->lastActivity > $fiveMinutesAgo;
    }

    public function isLongRunning(): bool
    {
        if (!$this->createdAt) {
            return false;
        }

        $interval = $this->getDuration();
        return $interval->h >= 4; // Plus de 4 heures
    }

    public function regenerateToken(): string
    {
        $this->sessionToken = bin2hex(random_bytes(32));
        return $this->sessionToken;
    }

    public function getIpInfo(): array
    {
        return [
            'ip' => $this->ipAddress,
            'isLocal' => $this->isLocalIp(),
            'isPrivate' => $this->isPrivateIp(),
        ];
    }

    public function isLocalIp(): bool
    {
        return in_array($this->ipAddress, ['127.0.0.1', '::1', 'localhost']);
    }

    public function isPrivateIp(): bool
    {
        if (!$this->ipAddress) {
            return false;
        }

        return !filter_var(
            $this->ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    // ====================================================
    // GETTERS AND SETTERS COMPLETS
    // ====================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(string $sessionToken): static
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getLastActivity(): ?\DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(\DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // ====================================================
    // MÉTHODES UTILITAIRES
    // ====================================================

    public function __toString(): string
    {
        return sprintf(
            'Session %s - %s (%s)',
            $this->user?->getLogin() ?: 'N/A',
            $this->ipAddress ?: 'N/A',
            $this->isValid() ? 'Actif' : 'Inactif'
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user?->getId(),
            'userLogin' => $this->user?->getLogin(),
            'userFullName' => $this->user?->getFullName(),
            'sessionToken' => substr($this->sessionToken ?? '', 0, 8) . '...', // Tronquer pour sécurité
            'ipAddress' => $this->ipAddress,
            'isLocal' => $this->isLocalIp(),
            'isPrivate' => $this->isPrivateIp(),
            'lastActivity' => $this->lastActivity?->format('Y-m-d H:i:s'),
            'expiresAt' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'isActive' => $this->isActive,
            'isExpired' => $this->isExpired(),
            'isValid' => $this->isValid(),
            'remainingTime' => $this->getRemainingTimeFormatted(),
            'duration' => $this->getDurationFormatted(),
            'timeSinceLastActivity' => $this->getTimeSinceLastActivity(),
            'shouldShowWarning' => $this->shouldShowWarning(),
            'isRecentlyActive' => $this->isRecentlyActive(),
            'isLongRunning' => $this->isLongRunning(),
        ];
    }

    public function getSessionInfo(): array
    {
        return [
            'token' => $this->sessionToken,
            'status' => $this->getStatus(),
            'user' => $this->user?->getDisplayName(),
            'ip' => $this->ipAddress,
            'created' => $this->createdAt?->format('d/m/Y H:i'),
            'lastActivity' => $this->lastActivity?->format('d/m/Y H:i'),
            'expires' => $this->expiresAt?->format('d/m/Y H:i'),
            'remainingTime' => $this->getRemainingTimeFormatted(),
        ];
    }

    public function getStatus(): string
    {
        if (!$this->isActive) {
            return 'Terminée';
        }

        if ($this->isExpired()) {
            return 'Expirée';
        }

        if ($this->shouldShowWarning()) {
            return 'Bientôt expirée';
        }

        if ($this->isRecentlyActive()) {
            return 'Active';
        }

        return 'Inactive';
    }

    public function getStatusBadge(): array
    {
        $status = $this->getStatus();

        return match($status) {
            'Active' => [
                'class' => 'bg-green-100 text-green-800',
                'text' => 'Active'
            ],
            'Inactive' => [
                'class' => 'bg-yellow-100 text-yellow-800',
                'text' => 'Inactive'
            ],
            'Bientôt expirée' => [
                'class' => 'bg-orange-100 text-orange-800',
                'text' => 'Expire bientôt'
            ],
            'Expirée' => [
                'class' => 'bg-red-100 text-red-800',
                'text' => 'Expirée'
            ],
            'Terminée' => [
                'class' => 'bg-gray-100 text-gray-800',
                'text' => 'Terminée'
            ],
            default => [
                'class' => 'bg-gray-100 text-gray-800',
                'text' => $status
            ]
        };
    }

    public function canBeExtended(): bool
    {
        return $this->isActive && !$this->isExpired();
    }

    public function getSecurityLevel(): string
    {
        $score = 0;

        // IP locale = plus sûr
        if ($this->isLocalIp()) {
            $score += 2;
        } elseif ($this->isPrivateIp()) {
            $score += 1;
        }

        // Session récente = plus sûr
        if ($this->isRecentlyActive()) {
            $score += 2;
        }

        // Session pas trop longue = plus sûr
        if (!$this->isLongRunning()) {
            $score += 1;
        }

        return match(true) {
            $score >= 4 => 'Élevé',
            $score >= 2 => 'Moyen',
            default => 'Faible'
        };
    }

    public function shouldAutoLogout(): bool
    {
        // Auto-logout si inactif depuis plus de 30 minutes
        if (!$this->lastActivity) {
            return true;
        }

        $thirtyMinutesAgo = new \DateTime('-30 minutes');
        return $this->lastActivity < $thirtyMinutesAgo;
    }

    public function isSuspicious(): bool
    {
        // Session suspecte si elle vient d'une IP non privée
        // et qu'elle dure depuis très longtemps
        return !$this->isPrivateIp() && $this->isLongRunning();
    }
}
