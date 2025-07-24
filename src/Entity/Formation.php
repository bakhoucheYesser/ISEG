<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
#[ORM\HasLifecycleCallbacks]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?AcademicLevel $academicLevel = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    private ?int $durationMonths = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $totalPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $registrationFees = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Enrollment::class)]
    private Collection $enrollments;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: ClassRoom::class)]
    private Collection $classes;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function calculateMonthlyPrice(): float
    {
        return (float) $this->totalPrice / 12;
    }

    public function calculateQuarterlyPrice(): float
    {
        return (float) $this->totalPrice / 4;
    }

    public function calculateSemesterPrice(): float
    {
        return (float) $this->totalPrice / 2;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getAcademicLevel(): ?AcademicLevel
    {
        return $this->academicLevel;
    }

    public function setAcademicLevel(?AcademicLevel $academicLevel): static
    {
        $this->academicLevel = $academicLevel;
        return $this;
    }

    public function getDurationMonths(): ?int
    {
        return $this->durationMonths;
    }

    public function setDurationMonths(int $durationMonths): static
    {
        $this->durationMonths = $durationMonths;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getRegistrationFees(): ?string
    {
        return $this->registrationFees;
    }

    public function setRegistrationFees(string $registrationFees): static
    {
        $this->registrationFees = $registrationFees;
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

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setFormation($this);
        }
        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            if ($enrollment->getFormation() === $this) {
                $enrollment->setFormation(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ClassRoom>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(ClassRoom $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
            $class->setFormation($this);
        }
        return $this;
    }

    public function removeClass(ClassRoom $class): static
    {
        if ($this->classes->removeElement($class)) {
            if ($class->getFormation() === $this) {
                $class->setFormation(null);
            }
        }
        return $this;
    }
}
