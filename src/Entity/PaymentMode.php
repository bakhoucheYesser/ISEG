<?php

namespace App\Entity;

use App\Repository\PaymentModeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentModeRepository::class)]
#[ORM\Table(name: 'payment_modes')]
class PaymentMode
{
    public const MONTHLY = 'MONTHLY';
    public const QUARTERLY = 'QUARTERLY';
    public const SEMESTER = 'SEMESTER';
    public const ANNUAL = 'ANNUAL';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Assert\Choice(choices: [self::MONTHLY, self::QUARTERLY, self::SEMESTER, self::ANNUAL])]
    private ?string $code = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    private ?int $frequencyMonths = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'paymentMode')]
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

    public function calculateInstallments(float $totalAmount): array
    {
        $installmentCount = 12 / $this->frequencyMonths;
        $installmentAmount = $totalAmount / $installmentCount;

        $installments = [];
        for ($i = 1; $i <= $installmentCount; $i++) {
            $installments[] = [
                'number' => $i,
                'amount' => $installmentAmount,
                'due_date' => (new \DateTime())->modify("+{$this->frequencyMonths} months")
            ];
        }

        return $installments;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getFrequencyMonths(): ?int
    {
        return $this->frequencyMonths;
    }

    public function setFrequencyMonths(?int $frequencyMonths): void
    {
        $this->frequencyMonths = $frequencyMonths;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
