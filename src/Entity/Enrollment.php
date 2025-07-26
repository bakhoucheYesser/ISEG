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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'étudiant est obligatoire')]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La formation est obligatoire')]
    private ?Formation $formation = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La classe est obligatoire')]
    private ?ClassRoom $classRoom = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le mode de paiement est obligatoire')]
    private ?PaymentMode $paymentMode = null;

    #[ORM\Column(length: 9)]
    #[Assert\NotBlank(message: 'L\'année académique est obligatoire')]
    #[Assert\Regex(pattern: '/^\d{4}-\d{4}$/', message: 'Format: YYYY-YYYY')]
    private ?string $academicYear = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date d\'inscription est obligatoire')]
    private ?\DateTimeInterface $enrollmentDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive(message: 'Le montant total doit être positif')]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive(message: 'Les frais d\'inscription doivent être positifs')]
    private ?string $registrationFees = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive(message: 'Les frais de formation doivent être positifs')]
    private ?string $formationFees = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\PositiveOrZero(message: 'Le montant payé doit être positif ou zéro')]
    private string $totalPaid = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero(message: 'Le montant restant doit être positif ou zéro')]
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
    #[Assert\NotNull(message: 'Le créateur est obligatoire')]
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

        // Initialiser les statuts par défaut
        $this->registrationStatus = RegistrationStatus::PENDING;
        $this->paymentStatus = PaymentStatus::NOT_PAID;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();

        if ($this->enrollmentDate === null) {
            $this->enrollmentDate = new \DateTime();
        }
    }

    public function calculateRemainingAmount(): float
    {
        return (float)$this->totalAmount - (float)$this->totalPaid;
    }

    public function isFullyPaid(): bool
    {
        return $this->calculateRemainingAmount() <= 0;
    }

    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid()) {
            $this->paymentStatus = PaymentStatus::FULLY_PAID;
        } elseif ((float)$this->totalPaid > 0) {
            $this->paymentStatus = PaymentStatus::PARTIAL;
        } else {
            $this->paymentStatus = PaymentStatus::NOT_PAID;
        }

        $this->remainingAmount = (string)$this->calculateRemainingAmount();
    }

    // Getters et setters...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    public function getClassRoom(): ?ClassRoom
    {
        return $this->classRoom;
    }

    public function setClassRoom(?ClassRoom $classRoom): static
    {
        $this->classRoom = $classRoom;
        return $this;
    }

    public function getPaymentMode(): ?PaymentMode
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(?PaymentMode $paymentMode): static
    {
        $this->paymentMode = $paymentMode;
        return $this;
    }

    public function getAcademicYear(): ?string
    {
        return $this->academicYear;
    }

    public function setAcademicYear(?string $academicYear): static
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    public function getEnrollmentDate(): ?\DateTimeInterface
    {
        return $this->enrollmentDate;
    }

    public function setEnrollmentDate(?\DateTimeInterface $enrollmentDate): static
    {
        $this->enrollmentDate = $enrollmentDate;
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getRegistrationFees(): ?string
    {
        return $this->registrationFees;
    }

    public function setRegistrationFees(?string $registrationFees): static
    {
        $this->registrationFees = $registrationFees;
        return $this;
    }

    public function getFormationFees(): ?string
    {
        return $this->formationFees;
    }

    public function setFormationFees(?string $formationFees): static
    {
        $this->formationFees = $formationFees;
        return $this;
    }

    public function getTotalPaid(): string
    {
        return $this->totalPaid;
    }

    public function setTotalPaid(string $totalPaid): static
    {
        $this->totalPaid = $totalPaid;
        return $this;
    }

    public function getRemainingAmount(): ?string
    {
        return $this->remainingAmount;
    }

    public function setRemainingAmount(?string $remainingAmount): static
    {
        $this->remainingAmount = $remainingAmount;
        return $this;
    }

    public function getRegistrationStatus(): RegistrationStatus
    {
        return $this->registrationStatus;
    }

    public function setRegistrationStatus(RegistrationStatus $registrationStatus): static
    {
        $this->registrationStatus = $registrationStatus;
        return $this;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
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

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
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

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function setPayments(Collection $payments): static
    {
        $this->payments = $payments;
        return $this;
    }

    public function getPaymentInstallments(): Collection
    {
        return $this->paymentInstallments;
    }

    public function setPaymentInstallments(Collection $paymentInstallments): static
    {
        $this->paymentInstallments = $paymentInstallments;
        return $this;
    }
}
