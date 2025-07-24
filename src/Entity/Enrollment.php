<?php

namespace App\Entity;

use App\Enum\RegistrationStatus;
use App\Enum\PaymentStatus;
use App\Repository\EnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollments')]
#[ORM\HasLifecycleCallbacks]
class Enrollment
{

    public const REGISTRATION_PENDING = 'PENDING';
    public const REGISTRATION_CONFIRMED = 'CONFIRMED';
    public const REGISTRATION_CANCELLED = 'CANCELLED';
    public const REGISTRATION_REJECTED = 'REJECTED';

    public const PAYMENT_NOT_PAID = 'NOT_PAID';
    public const PAYMENT_PARTIAL = 'PARTIAL';
    public const PAYMENT_FULLY_PAID = 'FULLY_PAID';
    public const PAYMENT_REFUNDED = 'REFUNDED';
    public const PAYMENT_CANCELLED = 'CANCELLED';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Formation $formation = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?ClassRoom $classRoom = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?PaymentMode $paymentMode = null;

    #[ORM\Column(length: 9)]
    #[Assert\Regex(pattern: '/^\d{4}-\d{4}$/', message: 'Format: YYYY-YYYY')]
    private ?string $academicYear = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $enrollmentDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $registrationFees = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $formationFees = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\PositiveOrZero]
    private string $totalPaid = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $remainingAmount = null;

    #[ORM\Column(enumType: RegistrationStatus::class, type: Types::STRING, options: ['default' => 'PENDING'])]
    private RegistrationStatus $registrationStatus = RegistrationStatus::PENDING;

    #[ORM\Column(enumType: PaymentStatus::class, type: Types::STRING, options: ['default' => 'NOT_PAID'])]
    private PaymentStatus $paymentStatus = PaymentStatus::NOT_PAID;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $createdBy = null;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'enrollment', cascade: ['remove'])]
    private Collection $payments;

    #[ORM\OneToMany(targetEntity: PaymentInstallment::class, mappedBy: 'enrollment', cascade: ['persist', 'remove'])]
    private Collection $paymentInstallments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->paymentInstallments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->enrollmentDate = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function calculateRemainingAmount(): float
    {
        return (float) $this->totalAmount - (float) $this->totalPaid;
    }

    public function isFullyPaid(): bool
    {
        return $this->calculateRemainingAmount() <= 0;
    }

    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid()) {
            $this->paymentStatus = PaymentStatus::FULLY_PAID;
        } elseif ((float) $this->totalPaid > 0) {
            $this->paymentStatus = PaymentStatus::PARTIAL;
        } else {
            $this->paymentStatus = PaymentStatus::NOT_PAID;
        }

        $this->remainingAmount = (string) $this->calculateRemainingAmount();
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getStudent(): ?Student { return $this->student; }
    public function setStudent(?Student $student): void { $this->student = $student; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): void { $this->formation = $formation; }

    public function getClassRoom(): ?ClassRoom { return $this->classRoom; }
    public function setClassRoom(?ClassRoom $classRoom): void { $this->classRoom = $classRoom; }

    public function getPaymentMode(): ?PaymentMode { return $this->paymentMode; }
    public function setPaymentMode(?PaymentMode $paymentMode): void { $this->paymentMode = $paymentMode; }

    public function getAcademicYear(): ?string { return $this->academicYear; }
    public function setAcademicYear(?string $academicYear): void { $this->academicYear = $academicYear; }

    public function getEnrollmentDate(): ?\DateTimeInterface { return $this->enrollmentDate; }
    public function setEnrollmentDate(?\DateTimeInterface $enrollmentDate): void { $this->enrollmentDate = $enrollmentDate; }

    public function getTotalAmount(): ?string { return $this->totalAmount; }
    public function setTotalAmount(?string $totalAmount): void { $this->totalAmount = $totalAmount; }

    public function getRegistrationFees(): ?string { return $this->registrationFees; }
    public function setRegistrationFees(?string $registrationFees): void { $this->registrationFees = $registrationFees; }

    public function getFormationFees(): ?string { return $this->formationFees; }
    public function setFormationFees(?string $formationFees): void { $this->formationFees = $formationFees; }

    public function getTotalPaid(): string { return $this->totalPaid; }
    public function setTotalPaid(string $totalPaid): void { $this->totalPaid = $totalPaid; }

    public function getRemainingAmount(): ?string { return $this->remainingAmount; }
    public function setRemainingAmount(?string $remainingAmount): void { $this->remainingAmount = $remainingAmount; }

    public function getRegistrationStatus(): RegistrationStatus { return $this->registrationStatus; }
    public function setRegistrationStatus(RegistrationStatus $registrationStatus): void { $this->registrationStatus = $registrationStatus; }

    public function getPaymentStatus(): PaymentStatus { return $this->paymentStatus; }
    public function setPaymentStatus(PaymentStatus $paymentStatus): void { $this->paymentStatus = $paymentStatus; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void { $this->updatedAt = $updatedAt; }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }
    public function setCreatedBy(?User $createdBy): void { $this->createdBy = $createdBy; }

    public function getPayments(): Collection { return $this->payments; }
    public function setPayments(Collection $payments): void { $this->payments = $payments; }

    public function getPaymentInstallments(): Collection { return $this->paymentInstallments; }
    public function setPaymentInstallments(Collection $paymentInstallments): void { $this->paymentInstallments = $paymentInstallments; }
}
