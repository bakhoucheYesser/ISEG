<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_PAYMENT = 'PAYMENT';
    public const ACTION_VALIDATION = 'VALIDATION';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_IMPORT = 'IMPORT';
    public const ACTION_VIEW = 'VIEW';

    public const SEVERITY_LOW = 'LOW';
    public const SEVERITY_MEDIUM = 'MEDIUM';
    public const SEVERITY_HIGH = 'HIGH';
    public const SEVERITY_CRITICAL = 'CRITICAL';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [
        self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,
        self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_PAYMENT,
        self::ACTION_VALIDATION, self::ACTION_EXPORT, self::ACTION_IMPORT, self::ACTION_VIEW
    ])]
    private ?string $action = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $tableName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $recordId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $oldValues = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $newValues = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\Ip]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 20, options: ['default' => self::SEVERITY_LOW])]
    #[Assert\Choice(choices: [self::SEVERITY_LOW, self::SEVERITY_MEDIUM, self::SEVERITY_HIGH, self::SEVERITY_CRITICAL])]
    private string $severity = self::SEVERITY_LOW;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;


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

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): static
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getRecordId(): ?int
    {
        return $this->recordId;
    }

    public function setRecordId(?int $recordId): static
    {
        $this->recordId = $recordId;
        return $this;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function setOldValues(?array $oldValues): static
    {
        $this->oldValues = $oldValues;
        return $this;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function setNewValues(?array $newValues): static
    {
        $this->newValues = $newValues;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
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

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ====================================================
    // MÉTHODES MÉTIER
    // ====================================================

    public static function create(
        ?User   $user,
        string  $action,
        string  $tableName,
        ?int    $recordId = null,
        ?array  $oldValues = null,
        ?array  $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self
    {
        $log = new self();
        $log->user = $user;
        $log->action = $action;
        $log->tableName = $tableName;
        $log->recordId = $recordId;
        $log->oldValues = $oldValues;
        $log->newValues = $newValues;
        $log->ipAddress = $ipAddress;
        $log->userAgent = $userAgent;
        $log->severity = self::determineSeverity($action, $tableName);

        return $log;
    }

    public static function determineSeverity(string $action, string $tableName): string
    {
        // Actions critiques
        if (in_array($action, [self::ACTION_DELETE, self::ACTION_VALIDATION])) {
            return self::SEVERITY_CRITICAL;
        }

        // Actions importantes sur certaines tables
        if ($action === self::ACTION_UPDATE && in_array($tableName, ['users', 'payments'])) {
            return self::SEVERITY_HIGH;
        }

        // Actions de connexion/déconnexion
        if (in_array($action, [self::ACTION_LOGIN, self::ACTION_LOGOUT])) {
            return self::SEVERITY_MEDIUM;
        }

        // Actions de création et autres
        return self::SEVERITY_LOW;
    }

    public function getFormattedAction(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'Création',
            self::ACTION_UPDATE => 'Modification',
            self::ACTION_DELETE => 'Suppression',
            self::ACTION_LOGIN => 'Connexion',
            self::ACTION_LOGOUT => 'Déconnexion',
            self::ACTION_PAYMENT => 'Paiement',
            self::ACTION_VALIDATION => 'Validation',
            self::ACTION_EXPORT => 'Export',
            self::ACTION_IMPORT => 'Import',
            self::ACTION_VIEW => 'Consultation',
            default => $this->action
        };
    }

    public function getFormattedSeverity(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'Faible',
            self::SEVERITY_MEDIUM => 'Moyen',
            self::SEVERITY_HIGH => 'Élevé',
            self::SEVERITY_CRITICAL => 'Critique',
            default => $this->severity
        };
    }
}

//    public function getSeverityBadge(): array
//    {
//        return match($this->severity) {
//            self::SEVERITY_LOW => [
//                'class' => 'bg-gray-100 text-gray-800',
//                'text' => 'Faible'
//            ],
//            self::SEVERITY_MEDIUM => [
//                'class' => 'bg-blue-100 text-blue-800',
//                'text' => 'Moyen'
//            ],
//            self::SEVERITY_HIGH => [
//                'class' => 'bg-yellow-100 text-yellow-800',
//                'text' => 'Élevé'
//            ],
//            self::SEVERITY_CRITICAL => [
//                'class' => 'bg-red-100 text-red-800',
//                'text' => 'Critique'
//            ],
//            default => [
//                'class' => 'bg-gray-100 text-gray-800',
//                'text' => $this->severity
//            ]
//        ];
//    }
//
//    public function getActionBadge(): array
//    {
//        return match($this->action) {
//            self::ACTION_CREATE => [
//                'class' => 'bg-green-100 text-green-800',
//                'text' => 'Création'
//            ],
//            self::ACTION_UPDATE => [
//                'class' => 'bg-blue-100 text-blue-800',
//                'text' => 'Modification'
//            ],
//            self::ACTION_DELETE => [
//                'class' => 'bg-red-100 text-red-800',
//                'text' => 'Suppression'
//            ],
//            self::ACTION_LOGIN => [
//                'class' => 'bg-indigo-100 text-indigo-800',
//                'text' => 'Connexion'
//            ],
//            self::ACTION_LOGOUT => [
//                'class' => 'bg-gray-100 text-gray-800',
//                'text' => 'Déconnexion'
//            ],
//            self::ACTION_PAYMENT => [
//                'class' => 'bg-yellow-100 text-yellow-800',
//                'text' => 'Paiement'
//            ],
//            self::ACTION_VALIDATION => [
//                'class' => 'bg-purple-100 text-purple-800',
//                'text' => 'Validation'
//            ],
//            default => [
//                'class' => 'bg-gray-100 text-gray-800',
//                'text' => $this->getFormattedAction()
//            ]
//        ];
//    }
//
//    public function getTimestamp(): string
//    {
//        return $this->createdAt?->format('d/m/Y H:i:s') ?? '';
//    }
//
//    public function getTimeAgo(): string
//    {
//        if (!$this->createdAt) {
//            return 'Inconnu';
//        }
//
//        $now = new \DateTime();
//        $interval = $now->diff($this->createdAt);
//
//        if ($interval->d > 0) {
//            return sprintf('%d jour(s)', $interval->d);
//        } elseif ($interval->h > 0) {
//            return sprintf('%dh %02dm', $interval->h, $interval->i);
//        } elseif ($interval->i > 0) {
//            return sprintf('%dm', $interval->i);
//        } else {
//            return 'À l\'instant';
//        }
//    }
//
//    public function hasChanges(): bool
//    {
//        return !empty($this->oldValues) || !empty($this->newValues);
//    }
//
//    public function getChangedFields(): array
//    {
//        if (!$this->hasChanges()) {
//            return [];
//        }
//
//        $changedFields = [];
//        $oldValues = $this->oldValues ?? [];
//        $newValues = $this->newValues ?? [];
//
//        $allFields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
//
//        foreach ($allFields as $field) {
//            $oldValue = $oldValues[$field] ?? null;
//            $newValue = $newValues[$field] ?? null;
//
//            if ($oldValue !== $newValue) {
//                $changedFields[$field] = [
//                    'old' => $oldValue,
//                    'new' => $newValue
//                ];
//            }
//        }
//
//        return $changedFields;
//    }
//
//    public function isRecent(): bool
//    {
//        if (!$this->createdAt) {
//            return false;
//        }
//
//        $oneHourAgo = new \DateTime('-1 hour');
//        return $this->createdAt > $oneHourAgo;
//    }
//
//    public function isSuspicious(): bool
//    {
//        // Log suspect si action critique depuis IP non privée
//        $criticalActions = [self::ACTION_DELETE, self::ACTION_VALIDATION];
//
//        if (!in_array($this->action, $criticalActions)) {
//            return false;
//        }
//
//        if (!$this->ipAddress) {
//            return true; // Pas d'IP = suspect
//        }
//
//        // Vérifier si IP privée
//        return !filter_var(
//            $this->ipAddress,
//            FILTER_VALIDATE_IP,
//            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
//        );
//    }
//
//
//
//    // ====================================================
//    // MÉTHODES UTILITAIRES
//    // ====================================================
//
//    public function __toString(): string
//    {
//        $userName = $this->user?->getFullName() ?? 'Système';
//        return sprintf(
//            '%s - %s sur %s (%s)',
//            $userName,
//            $this->getFormattedAction(),
//            $this->tableName ?: 'N/A',
//            $this->getTimestamp()
//        );
//    }
//
//    public function toArray(): array
//    {
//        return [
//            'id' => $this->id,
//            'user' => $this->user?->getDisplayName(),
//            'action' => $this->action,
//            'formattedAction' => $this->getFormattedAction(),
//            'tableName' => $this->tableName,
//            'recordId' => $this->recordId,
//            'oldValues' => $this->oldValues,
//            'newValues' => $this->newValues,
//            'changedFields' => $this->getChangedFields(),
//            'ipAddress' => $this->ipAddress,
//            'userAgent' => $this->userAgent,
//            'severity' => $this->severity,
//            'formattedSeverity' => $this->getFormattedSeverity(),
//            'description' => $this->description,
//            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
//            'timestamp' => $this->getTimestamp(),
//            'timeAgo' => $this->getTimeAgo(),
//            'isRecent' => $this->isRecent(),
//            'isSuspicious' => $this->isSuspicious(),
//            'hasChanges' => $this->hasChanges(),
//        ];
//    }
//
//    public function getSummary(): string
//    {
//        $userName = $this->user?->getFullName() ?? 'Système';
//        $action = $this->getFormattedAction();
//
//        if ($this->recordId) {
//            return sprintf('%s a effectué %s sur %s #%d', $userName, $action, $this->tableName, $this->recordId);
//        }
//
//        return sprintf('%s a effectué %s sur %s', $userName, $action, $this->tableName);
//    }
//}
