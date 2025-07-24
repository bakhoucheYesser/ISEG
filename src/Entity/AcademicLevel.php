<?php


namespace App\Entity;

use App\Repository\AcademicLevelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AcademicLevelRepository::class)]
#[ORM\Table(name: 'academic_levels')]
class AcademicLevel
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
private ?int $id = null;

#[ORM\Column(length: 100)]
#[Assert\NotBlank]
private ?string $name = null;

#[ORM\Column(length: 10, unique: true)]
#[Assert\NotBlank]
private ?string $code = null;

#[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $description = null;

#[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
private bool $isActive = true;

#[ORM\Column(type: Types::DATETIME_MUTABLE)]
private ?\DateTimeInterface $createdAt = null;

#[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'academicLevel')]
private Collection $formations;

#[ORM\OneToMany(targetEntity: ClassRoom::class, mappedBy: 'academicLevel')]
private Collection $classes;

public function __construct()
{
$this->formations = new ArrayCollection();
$this->classes = new ArrayCollection();
$this->createdAt = new \DateTime();
}

public function __toString(): string
{
return $this->name ?? '';
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

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setAcademicLevel($this);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getAcademicLevel() === $this) {
                $formation->setAcademicLevel(null);
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
            $class->setAcademicLevel($this);
        }
        return $this;
    }

    public function removeClass(ClassRoom $class): static
    {
        if ($this->classes->removeElement($class)) {
            if ($class->getAcademicLevel() === $this) {
                $class->setAcademicLevel(null);
            }
        }
        return $this;
    }

}
