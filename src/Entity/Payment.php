<?php

namespace App\Entity;

use App\Enum\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    public const TYPE_REGISTRATION = 'REGISTRATION';
    public const TYPE_FORMATION = 'FORMATION';
    public const TYPE_PARTIAL = 'PARTIAL';
    public const TYPE_FULL = 'FULL';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: Types::STRING, enumType: PaymentType::class)]
    #[Assert\NotBlank]
    private ?PaymentType $paymentType = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // Relations corrigées
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Enrollment::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Enrollment $enrollment = null;

    #[ORM\OneToOne(targetEntity: Receipt::class, mappedBy: 'payment', cascade: ['persist', 'remove'])]
    private ?Receipt $receipt = null;

    #[ORM\ManyToOne(targetEntity: PaymentInstallment::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?PaymentInstallment $installment = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->paymentDate = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ====================================================
    // MÉTHODES MÉTIER
    // ====================================================

    public function getFormattedAmount(): string
    {
        return number_format((float) $this->amount, 2, ',', ' ') . ' DT';
    }

    public function getFormattedPaymentType(): string
    {
        return match($this->paymentType) {
            PaymentType::REGISTRATION => 'Frais d\'inscription',
            PaymentType::FORMATION => 'Frais de formation',
            PaymentType::PARTIAL => 'Paiement partiel',
            PaymentType::FULL => 'Paiement complet',
            default => $this->paymentType->value ?? 'Inconnu'
        };
    }

    public function getFormattedPaymentMethod(): string
    {
        return match($this->paymentMethod) {
            'CASH' => 'Espèces',
            'CHECK' => 'Chèque',
            'BANK_TRANSFER' => 'Virement bancaire',
            'CREDIT_CARD' => 'Carte de crédit',
            'ONLINE' => 'Paiement en ligne',
            default => $this->paymentMethod ?? 'Autre'
        };
    }

    public function isRegistrationPayment(): bool
    {
        return $this->paymentType === PaymentType::REGISTRATION;
    }

    public function isFormationPayment(): bool
    {
        return $this->paymentType === PaymentType::FORMATION;
    }

    public function isPartialPayment(): bool
    {
        return $this->paymentType === PaymentType::PARTIAL;
    }

    public function isFullPayment(): bool
    {
        return $this->paymentType === PaymentType::FULL;
    }

    public function hasReceipt(): bool
    {
        return $this->receipt !== null;
    }

    public function generateReference(): string
    {
        if (!$this->reference) {
            $this->reference = 'PAY-' . date('Y') . '-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
        }
        return $this->reference;
    }

    public function getTimeSincePayment(): string
    {
        if (!$this->paymentDate) {
            return 'Inconnu';
        }

        $now = new \DateTime();
        $interval = $now->diff($this->paymentDate);

        if ($interval->days > 0) {
            return $interval->format('%d jour(s)');
        } elseif ($interval->h > 0) {
            return $interval->format('%h heure(s)');
        } elseif ($interval->i > 0) {
            return $interval->format('%i minute(s)');
        } else {
            return 'À l\'instant';
        }
    }

    public function isRecentPayment(): bool
    {
        if (!$this->paymentDate) {
            return false;
        }

        $oneDayAgo = new \DateTime('-1 day');
        return $this->paymentDate > $oneDayAgo;
    }

    public function canBeModified(): bool
    {
        // Un paiement peut être modifié s'il n'a pas de reçu généré
        // et qu'il a été créé dans les dernières 24h
        if ($this->hasReceipt()) {
            return false;
        }

        if (!$this->createdAt) {
            return false;
        }

        $oneDayAgo = new \DateTime('-24 hours');
        return $this->createdAt > $oneDayAgo;
    }

    public function canBeDeleted(): bool
    {
        // Un paiement peut être supprimé s'il n'a pas de reçu
        // et qu'il a été créé dans la dernière heure
        if ($this->hasReceipt()) {
            return false;
        }

        if (!$this->createdAt) {
            return false;
        }

        $oneHourAgo = new \DateTime('-1 hour');
        return $this->createdAt > $oneHourAgo;
    }

    // ====================================================
    // GETTERS AND SETTERS
    // ====================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->paymentType;
    }

    public function setPaymentType(PaymentType $paymentType): static
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getEnrollment(): ?Enrollment
    {
        return $this->enrollment;
    }

    public function setEnrollment(?Enrollment $enrollment): static
    {
        $this->enrollment = $enrollment;
        return $this;
    }

    public function getReceipt(): ?Receipt
    {
        return $this->receipt;
    }

    public function setReceipt(?Receipt $receipt): static
    {
        // Si c'est la partie inverse de la relation OneToOne
        if ($receipt === null && $this->receipt !== null) {
            $this->receipt->setPayment(null);
        }

        // Si c'est un nouveau reçu
        if ($receipt !== null && $receipt->getPayment() !== $this) {
            $receipt->setPayment($this);
        }

        $this->receipt = $receipt;
        return $this;
    }

    public function getInstallment() : ?PaymentInstallment{
        return $this->installment;
    }
    public function setInstallment(?PaymentInstallment $installment): static{
        $this->installment = $installment;
        return $this;
    }

    // ====================================================
    // MÉTHODES UTILITAIRES
    // ====================================================

    public function __toString(): string
    {
        return sprintf('Paiement %s - %s - %s',
            $this->reference ?: '#' . $this->id,
            $this->getFormattedAmount(),
            $this->paymentDate?->format('d/m/Y') ?: 'N/A'
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'formattedAmount' => $this->getFormattedAmount(),
            'paymentDate' => $this->paymentDate?->format('Y-m-d H:i:s'),
            'paymentType' => $this->paymentType?->value,
            'formattedPaymentType' => $this->getFormattedPaymentType(),
            'paymentMethod' => $this->paymentMethod,
            'formattedPaymentMethod' => $this->getFormattedPaymentMethod(),
            'description' => $this->description,
            'reference' => $this->reference,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy?->getFullName(),
            'enrollment' => $this->enrollment?->getId(),
            'student' => $this->enrollment?->getStudent()?->getFullName(),
            'hasReceipt' => $this->hasReceipt(),
            'canBeModified' => $this->canBeModified(),
            'canBeDeleted' => $this->canBeDeleted(),
        ];
    }

    public function getAuditData(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'paymentDate' => $this->paymentDate?->format('Y-m-d H:i:s'),
            'paymentType' => $this->paymentType?->value,
            'paymentMethod' => $this->paymentMethod,
            'reference' => $this->reference,
            'enrollmentId' => $this->enrollment?->getId(),
            'createdBy' => $this->createdBy?->getId(),
        ];
    }

    public function getStatusBadge(): array
    {
        if (!$this->isActive) {
            return [
                'class' => 'bg-red-100 text-red-800',
                'text' => 'Inactif'
            ];
        }

        if ($this->hasReceipt()) {
            return [
                'class' => 'bg-green-100 text-green-800',
                'text' => 'Avec reçu'
            ];
        }

        if ($this->isRecentPayment()) {
            return [
                'class' => 'bg-blue-100 text-blue-800',
                'text' => 'Récent'
            ];
        }

        return [
            'class' => 'bg-yellow-100 text-yellow-800',
            'text' => 'En attente de reçu'
        ];
    }

    public function duplicate(): self
    {
        $duplicate = new self();
        $duplicate->setAmount($this->amount);
        $duplicate->setPaymentType($this->paymentType);
        $duplicate->setPaymentMethod($this->paymentMethod);
        $duplicate->setDescription($this->description . ' (Copie)');
        $duplicate->setEnrollment($this->enrollment);
        $duplicate->setCreatedBy($this->createdBy);

        return $duplicate;
    }

    public function updateFromArray(array $data): void
    {
        if (isset($data['amount'])) {
            $this->setAmount($data['amount']);
        }

        if (isset($data['paymentDate'])) {
            $this->setPaymentDate(new \DateTime($data['paymentDate']));
        }

        if (isset($data['paymentType']) && $data['paymentType'] instanceof PaymentType) {
            $this->setPaymentType($data['paymentType']);
        }

        if (isset($data['paymentMethod'])) {
            $this->setPaymentMethod($data['paymentMethod']);
        }

        if (isset($data['description'])) {
            $this->setDescription($data['description']);
        }

        if (isset($data['reference'])) {
            $this->setReference($data['reference']);
        }
    }
}
