<?php

namespace App\Entity;

use App\Enum\InstallmentStatus;
use App\Repository\PaymentInstallmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentInstallmentRepository::class)]
#[ORM\Table(name: 'payment_installments')]
class PaymentInstallment
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_OVERDUE = 'OVERDUE';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paymentInstallments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
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

    #[ORM\Column(enumType: InstallmentStatus::class, options: ['default' => 'PENDING'])]
    private InstallmentStatus $status = InstallmentStatus::PENDING;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\PositiveOrZero]
    private string $paidAmount = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function isOverdue(): bool
    {
        return $this->status === InstallmentStatus::PENDING &&
            $this->dueDate < new \DateTime() &&
            (float) $this->paidAmount < (float) $this->amount;
    }

    public function markAsPaid(): void
    {
        $this->status = InstallmentStatus::PAID;
        $this->paidDate = new \DateTime();
        $this->paidAmount = $this->amount;
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnrollment(): ?Enrollment
    {
        return $this->enrollment;
    }

    public function setEnrollment(?Enrollment $enrollment): void
    {
        $this->enrollment = $enrollment;
    }

    public function getInstallmentNumber(): ?int
    {
        return $this->installmentNumber;
    }

    public function setInstallmentNumber(?int $installmentNumber): void
    {
        $this->installmentNumber = $installmentNumber;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function getStatus(): InstallmentStatus
    {
        return $this->status;
    }

    public function setStatus(InstallmentStatus $status): void
    {
        $this->status = $status;
    }

    public function getPaidDate(): ?\DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?\DateTimeInterface $paidDate): void
    {
        $this->paidDate = $paidDate;
    }

    public function getPaidAmount(): string
    {
        return $this->paidAmount;
    }

    public function setPaidAmount(string $paidAmount): void
    {
        $this->paidAmount = $paidAmount;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
