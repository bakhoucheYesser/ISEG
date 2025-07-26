<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'payment_installments')]
class PaymentInstallment
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_OVERDUE = 'OVERDUE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Enrollment::class, inversedBy: 'paymentInstallments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Enrollment $enrollment = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    private ?int $installmentNumber = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'PENDING'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    // CORRECTION: Collection de paiements pour la relation bidirectionnelle
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'installment')]
    private Collection $payments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->payments = new ArrayCollection();
    }

    // ====================================================
    // MÉTHODES MÉTIER
    // ====================================================

    public function markAsPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->paidAt = new \DateTime();
    }

    public function markAsOverdue(): void
    {
        $this->status = self::STATUS_OVERDUE;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE ||
            ($this->dueDate && $this->dueDate < new \DateTime() && !$this->isPaid());
    }

    public function getFormattedAmount(): string
    {
        return number_format((float) $this->amount, 2, ',', ' ') . ' DT';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PAID => 'Payé',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_OVERDUE => 'En retard',
            default => 'Inconnu'
        };
    }

    // ====================================================
    // GETTERS AND SETTERS
    // ====================================================

    public function getId(): ?int
    {
        return $this->id;
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

    public function getInstallmentNumber(): ?int
    {
        return $this->installmentNumber;
    }

    public function setInstallmentNumber(int $installmentNumber): static
    {
        $this->installmentNumber = $installmentNumber;
        return $this;
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

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;
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

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setInstallment($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getInstallment() === $this) {
                $payment->setInstallment(null);
            }
        }

        return $this;
    }
}
