<?php

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
#[ORM\Table(name: 'students')]
#[ORM\HasLifecycleCallbacks]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{8}$/', message: 'CIN doit contenir 8 chiffres')]
    private ?string $cin = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 150, unique: true, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $placeOfBirth = null;

    #[ORM\Column(nullable: true, enumType: Gender::class)]
    private ?Gender $gender = null;

    #[ORM\Column(length: 50, options: ['default' => 'Tunisienne'])]
    private string $nationality = 'Tunisienne';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Enrollment::class, cascade: ['remove'])]
    private Collection $enrollments;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->registrationDate = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function calculateAge(): ?int
    {
        if (!$this->dateOfBirth) {
            return null;
        }

        return $this->dateOfBirth->diff(new \DateTime())->y;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(?string $cin): void
    {
        $this->cin = $cin;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    public function getPlaceOfBirth(): ?string
    {
        return $this->placeOfBirth;
    }

    public function setPlaceOfBirth(?string $placeOfBirth): void
    {
        $this->placeOfBirth = $placeOfBirth;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): void
    {
        $this->gender = $gender;
    }

    public function getNationality(): string
    {
        return $this->nationality;
    }

    public function setNationality(string $nationality): void
    {
        $this->nationality = $nationality;
    }

    public function getEmergencyContactName(): ?string
    {
        return $this->emergencyContactName;
    }

    public function setEmergencyContactName(?string $emergencyContactName): void
    {
        $this->emergencyContactName = $emergencyContactName;
    }

    public function getEmergencyContactPhone(): ?string
    {
        return $this->emergencyContactPhone;
    }

    public function setEmergencyContactPhone(?string $emergencyContactPhone): void
    {
        $this->emergencyContactPhone = $emergencyContactPhone;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(?\DateTimeInterface $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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
