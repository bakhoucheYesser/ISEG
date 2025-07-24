<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, enumType: UserRole::class)]
    #[Assert\Choice(choices: [UserRole::ADMIN, UserRole::AGENT])]
    private ?UserRole $role = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $fullName = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\Ip]
    private ?string $allowedIpAddress = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastActivityAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'createdBy')]
    private Collection $enrollments;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'createdBy')]
    private Collection $payments;

    #[ORM\OneToMany(targetEntity: UserSession::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $sessions;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getLogin(): ?string { return $this->login; }
    public function setLogin(string $login): static { $this->login = $login; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getRole(): ?UserRole { return $this->role; }
    public function setRole(UserRole $role): static { $this->role = $role; return $this; }

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(string $fullName): static { $this->fullName = $fullName; return $this; }

    public function getAllowedIpAddress(): ?string { return $this->allowedIpAddress; }
    public function setAllowedIpAddress(?string $allowedIpAddress): static { $this->allowedIpAddress = $allowedIpAddress; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getLastLoginAt(): ?\DateTimeInterface { return $this->lastLoginAt; }
    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static { $this->lastLoginAt = $lastLoginAt; return $this; }

    public function getLastActivityAt(): ?\DateTimeInterface { return $this->lastActivityAt; }
    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt): static { $this->lastActivityAt = $lastActivityAt; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getEnrollments(): Collection { return $this->enrollments; }
    public function addEnrollment(Enrollment $enrollment): static {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setCreatedBy($this);
        }
        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static {
        if ($this->enrollments->removeElement($enrollment)) {
            if ($enrollment->getCreatedBy() === $this) {
                $enrollment->setCreatedBy(null);
            }
        }
        return $this;
    }

    public function getPayments(): Collection { return $this->payments; }
    public function addPayment(Payment $payment): static {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setCreatedBy($this);
        }
        return $this;
    }

    public function removePayment(Payment $payment): static {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getCreatedBy() === $this) {
                $payment->setCreatedBy(null);
            }
        }
        return $this;
    }

    public function getSessions(): Collection { return $this->sessions; }
    public function addSession(UserSession $session): static {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setUser($this);
        }
        return $this;
    }

    public function removeSession(UserSession $session): static {
        if ($this->sessions->removeElement($session)) {
            if ($session->getUser() === $this) {
                $session->setUser(null);
            }
        }
        return $this;
    }

    public function getUserIdentifier(): string { return (string) $this->login; }

    public function getRoles(): array
    {
        return ['ROLE_' . $this->role->value];
    }

    public function eraseCredentials(): void {}

    public function updateLastActivity(): void
    {
        $this->lastActivityAt = new \DateTime();
    }

    public function updateLastLogin(): void
    {
        $this->lastLoginAt = new \DateTime();
        $this->updateLastActivity();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::AGENT;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function canAccessFromIp(string $clientIp): bool
    {
        return !$this->allowedIpAddress || $this->allowedIpAddress === $clientIp;
    }

    public function getActiveSessionsCount(): int
    {
        return $this->sessions->filter(fn($s) => $s->isActive() && !$s->isExpired())->count();
    }

    public function getLastActiveSession(): ?UserSession
    {
        $active = $this->sessions->filter(fn($s) => $s->isActive() && !$s->isExpired());
        return $active->isEmpty() ? null : $active->last();
    }

    public function getTotalEnrollmentsCreated(): int
    {
        return $this->enrollments->count();
    }

    public function getTotalPaymentsProcessed(): int
    {
        return $this->payments->count();
    }

    public function getPaymentsProcessedThisMonth(): int
    {
        $thisMonth = new \DateTime('first day of this month');
        return $this->payments->filter(fn($p) => $p->getCreatedAt() >= $thisMonth)->count();
    }

    public function getTotalAmountProcessedThisMonth(): float
    {
        $thisMonth = new \DateTime('first day of this month');
        return array_reduce($this->payments->toArray(), fn($carry, $p) =>
        $p->getCreatedAt() >= $thisMonth ? $carry + $p->getAmount() : $carry, 0.0);
    }

    public function getFormattedRole(): string
    {
        return match($this->role) {
            UserRole::ADMIN => 'Administrateur',
            UserRole::AGENT => 'Agent de paiement',
            default => 'Utilisateur'
        };
    }

    public function getInitials(): string
    {
        return implode('', array_map(fn($name) => strtoupper($name[0]), explode(' ', $this->fullName)));
    }

    public function getTimeSinceLastActivity(): ?string
    {
        if (!$this->lastActivityAt) return null;
        $now = new \DateTime();
        $interval = $now->diff($this->lastActivityAt);
        return match (true) {
            $interval->days > 0 => $interval->format('%d jour(s)'),
            $interval->h > 0 => $interval->format('%h heure(s)'),
            $interval->i > 0 => $interval->format('%i minute(s)'),
            default => 'À l\'instant'
        };
    }

    public function isOnline(): bool
    {
        return $this->lastActivityAt && $this->lastActivityAt > new \DateTime('-5 minutes');
    }

    public function deactivateAllSessions(): void
    {
        foreach ($this->sessions as $session) {
            $session->setIsActive(false);
        }
    }

    public function __toString(): string
    {
        return $this->fullName ?? $this->login ?? '';
    }

    public function validateIpAccess(string $clientIp): bool
    {
        return $this->canAccessFromIp($clientIp);
    }

    public function canCreateEnrollments(): bool
    {
        return $this->isActive() && ($this->isAdmin() || $this->isAgent());
    }

    public function canProcessPayments(): bool
    {
        return $this->isActive() && ($this->isAdmin() || $this->isAgent());
    }

    public function canValidatePayments(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canAccessStatistics(): bool
    {
        return $this->isActive() && ($this->isAdmin() || $this->isAgent());
    }

    public function canExportData(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function getDisplayName(): string
    {
        return $this->fullName . ' (' . $this->login . ')';
    }

    public function getAuditData(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'fullName' => $this->fullName,
            'role' => $this->role->value ?? null,
            'isActive' => $this->isActive,
            'allowedIpAddress' => $this->allowedIpAddress,
        ];
    }
}
