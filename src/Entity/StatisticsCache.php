<?php

// src/Entity/StatisticsCache.php (COMPLET)
namespace App\Entity;

use App\Repository\StatisticsCacheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatisticsCacheRepository::class)]
#[ORM\Table(name: 'statistics_cache')]
class StatisticsCache
{
    public const CACHE_DURATION_HOURS = 24;

    // Types de statistiques supportés
    public const STAT_PAYMENT_RATE = 'payment_rate';
    public const STAT_STUDENTS_BY_LEVEL = 'students_by_level';
    public const STAT_PAYMENT_STATISTICS = 'payment_statistics';
    public const STAT_MONTHLY_REVENUE = 'monthly_revenue';
    public const STAT_ENROLLMENT_TRENDS = 'enrollment_trends';
    public const STAT_CLASS_OCCUPANCY = 'class_occupancy';

    // Types de périodes
    public const PERIOD_DAILY = 'daily';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_YEARLY = 'yearly';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $statType = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::PERIOD_DAILY, self::PERIOD_MONTHLY, self::PERIOD_YEARLY])]
    private ?string $periodType = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $periodValue = null;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $calculatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $expiresAt = null;

    public function __construct()
    {
        $this->calculatedAt = new \DateTime();
        // Cache expire après 24 heures
        $this->expiresAt = (new \DateTime())->modify('+' . self::CACHE_DURATION_HOURS . ' hours');
    }

    // ====================================================
    // MÉTHODES MÉTIER
    // ====================================================

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function refresh(): void
    {
        $this->calculatedAt = new \DateTime();
        $this->expiresAt = (new \DateTime())->modify('+' . self::CACHE_DURATION_HOURS . ' hours');
    }

    public function extendExpiry(int $hours = self::CACHE_DURATION_HOURS): void
    {
        $this->expiresAt = (new \DateTime())->modify("+{$hours} hours");
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !empty($this->data);
    }

    public function getAge(): \DateInterval
    {
        if (!$this->calculatedAt) {
            return new \DateInterval('P0D'); // 0 jours
        }

        return $this->calculatedAt->diff(new \DateTime());
    }

    public function getAgeFormatted(): string
    {
        $interval = $this->getAge();

        if ($interval->d > 0) {
            return sprintf('%d jour(s) %dh', $interval->d, $interval->h);
        } elseif ($interval->h > 0) {
            return sprintf('%dh %02dm', $interval->h, $interval->i);
        } elseif ($interval->i > 0) {
            return sprintf('%dm', $interval->i);
        } else {
            return 'Moins d\'1 minute';
        }
    }

    public function getRemainingTime(): \DateInterval
    {
        if ($this->isExpired()) {
            return new \DateInterval('PT0S'); // 0 secondes
        }

        return (new \DateTime())->diff($this->expiresAt);
    }

    public function getRemainingTimeFormatted(): string
    {
        if ($this->isExpired()) {
            return 'Expiré';
        }

        $interval = $this->getRemainingTime();

        if ($interval->d > 0) {
            return sprintf('%d jour(s) %dh', $interval->d, $interval->h);
        } elseif ($interval->h > 0) {
            return sprintf('%dh %02dm', $interval->h, $interval->i);
        } elseif ($interval->i > 0) {
            return sprintf('%dm', $interval->i);
        } else {
            return 'Moins d\'1 minute';
        }
    }

    // ====================================================
    // MÉTHODES DE RÉCUPÉRATION DES DONNÉES
    // ====================================================

    public function calculatePaymentRate(): float
    {
        return $this->data['payment_rate'] ?? 0.0;
    }

    public function getStudentsByLevel(): array
    {
        return $this->data['students_by_level'] ?? [];
    }

    public function getPaymentStatistics(): array
    {
        return $this->data['payment_statistics'] ?? [];
    }

    public function getMonthlyRevenue(): array
    {
        return $this->data['monthly_revenue'] ?? [];
    }

    public function getEnrollmentTrends(): array
    {
        return $this->data['enrollment_trends'] ?? [];
    }

    public function getClassOccupancy(): array
    {
        return $this->data['class_occupancy'] ?? [];
    }

    public function getValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function setValue(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function hasValue(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function removeValue(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clearData(): void
    {
        $this->data = [];
    }

    public function mergeData(array $newData): void
    {
        $this->data = array_merge($this->data, $newData);
    }

    // ====================================================
    // GETTERS AND SETTERS COMPLETS
    // ====================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatType(): ?string
    {
        return $this->statType;
    }

    public function setStatType(string $statType): static
    {
        $this->statType = $statType;
        return $this;
    }

    public function getPeriodType(): ?string
    {
        return $this->periodType;
    }

    public function setPeriodType(string $periodType): static
    {
        $this->periodType = $periodType;
        return $this;
    }

    public function getPeriodValue(): ?string
    {
        return $this->periodValue;
    }

    public function setPeriodValue(string $periodValue): static
    {
        $this->periodValue = $periodValue;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getCalculatedAt(): ?\DateTimeInterface
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(\DateTimeInterface $calculatedAt): static
    {
        $this->calculatedAt = $calculatedAt;
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

    // ====================================================
    // MÉTHODES UTILITAIRES
    // ====================================================

    public function __toString(): string
    {
        return sprintf(
            'Cache %s [%s-%s] - %s',
            $this->statType ?: 'N/A',
            $this->periodType ?: 'N/A',
            $this->periodValue ?: 'N/A',
            $this->isExpired() ? 'Expiré' : 'Valide'
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'statType' => $this->statType,
            'periodType' => $this->periodType,
            'periodValue' => $this->periodValue,
            'data' => $this->data,
            'calculatedAt' => $this->calculatedAt?->format('Y-m-d H:i:s'),
            'expiresAt' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'isExpired' => $this->isExpired(),
            'isValid' => $this->isValid(),
            'age' => $this->getAgeFormatted(),
            'remainingTime' => $this->getRemainingTimeFormatted(),
        ];
    }

    public function getCacheKey(): string
    {
        return sprintf('%s-%s-%s', $this->statType, $this->periodType, $this->periodValue);
    }

    public static function generateCacheKey(string $statType, string $periodType, string $periodValue): string
    {
        return sprintf('%s-%s-%s', $statType, $periodType, $periodValue);
    }
}
