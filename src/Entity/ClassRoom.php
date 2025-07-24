<?php

namespace App\Entity;


use App\Repository\ClassRoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClassRoomRepository::class)]
#[ORM\Table(name: 'classes')]
class ClassRoom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'classes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AcademicLevel $academicLevel = null;

    #[ORM\ManyToOne(inversedBy: 'classes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30])]
    #[Assert\Positive]
    private int $capacity = 30;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $currentStudents = 0;

    #[ORM\Column(length: 9)]
    #[Assert\Regex(pattern: '/^\d{4}-\d{4}$/', message: 'Format: YYYY-YYYY')]
    private ?string $academicYear = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'classRoom', targetEntity: Enrollment::class)]
    private Collection $enrollments;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function isFull(): bool
    {
        return $this->currentStudents >= $this->capacity;
    }

    public function addStudent(): bool
    {
        if (!$this->isFull()) {
            $this->currentStudents++;
            return true;
        }
        return false;
    }

    public function removeStudent(): void
    {
        if ($this->currentStudents > 0) {
            $this->currentStudents--;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAcademicLevel(): ?AcademicLevel
    {
        return $this->academicLevel;
    }

    public function setAcademicLevel(?AcademicLevel $academicLevel): void
    {
        $this->academicLevel = $academicLevel;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): void
    {
        $this->formation = $formation;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): void
    {
        $this->capacity = $capacity;
    }

    public function getCurrentStudents(): int
    {
        return $this->currentStudents;
    }

    public function setCurrentStudents(int $currentStudents): void
    {
        $this->currentStudents = $currentStudents;
    }

    public function getAcademicYear(): ?string
    {
        return $this->academicYear;
    }

    public function setAcademicYear(?string $academicYear): void
    {
        $this->academicYear = $academicYear;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function setEnrollments(Collection $enrollments): void
    {
        $this->enrollments = $enrollments;
    }


}
