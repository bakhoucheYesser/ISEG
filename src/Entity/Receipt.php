<?php

// src/Entity/Receipt.php (COMPLET)
namespace App\Entity;

use App\Repository\ReceiptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReceiptRepository::class)]
#[ORM\Table(name: 'receipts')]
class Receipt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'receipt', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Payment $payment = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $receiptNumber = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $studentName = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $formationName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $paymentType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $receiptContent = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $generatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $generatedBy = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $printedCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastPrintedAt = null;

    public function __construct()
    {
        $this->generatedAt = new \DateTime();
    }

    // ====================================================
    // MÉTHODES MÉTIER
    // ====================================================

    public function generatePDF(): string
    {
        // Logic to generate PDF receipt
        $filename = 'receipt_' . $this->receiptNumber . '.pdf';
        $this->pdfPath = '/receipts/' . $filename;
        return $this->pdfPath;
    }

    public function print(): void
    {
        $this->printedCount++;
        $this->lastPrintedAt = new \DateTime();
    }

    public function hasPDF(): bool
    {
        return !empty($this->pdfPath) && file_exists($this->getFullPdfPath());
    }

    public function getFullPdfPath(): string
    {
        if (!$this->pdfPath) {
            return '';
        }

        // Retourner le chemin complet vers le fichier PDF
        $projectDir = $_ENV['KERNEL_PROJECT_DIR'] ?? getcwd();
        return $projectDir . '/var' . $this->pdfPath;
    }

    public function getFormattedAmount(): string
    {
        return number_format((float) $this->amount, 2, ',', ' ') . ' DT';
    }

    public function getFormattedPaymentType(): string
    {
        return match($this->paymentType) {
            'REGISTRATION' => 'Frais d\'inscription',
            'FORMATION' => 'Frais de formation',
            'PARTIAL' => 'Paiement partiel',
            'FULL' => 'Paiement complet',
            default => $this->paymentType
        };
    }

    public function isPrinted(): bool
    {
        return $this->printedCount > 0;
    }

    public function getTimeSinceGenerated(): string
    {
        if (!$this->generatedAt) {
            return 'Inconnu';
        }

        $now = new \DateTime();
        $interval = $now->diff($this->generatedAt);

        if ($interval->days > 0) {
            return $interval->format('%d jour(s)');
        } elseif ($interval->h > 0) {
            return $interval->format('%h heure(s)');
        } elseif ($interval->i > 0) {
            return $interval->format('%i minute(s)');
        } else {
            return 'À l\'instant';
        }
    }

    public function getTimeSinceLastPrint(): ?string
    {
        if (!$this->lastPrintedAt) {
            return null;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->lastPrintedAt);

        if ($interval->days > 0) {
            return $interval->format('%d jour(s)');
        } elseif ($interval->h > 0) {
            return $interval->format('%h heure(s)');
        } elseif ($interval->i > 0) {
            return $interval->format('%i minute(s)');
        } else {
            return 'À l\'instant';
        }
    }

    // ====================================================
    // GETTERS AND SETTERS COMPLETS
    // ====================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(Payment $payment): static
    {
        $this->payment = $payment;
        return $this;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    public function setReceiptNumber(string $receiptNumber): static
    {
        $this->receiptNumber = $receiptNumber;
        return $this;
    }

    public function getStudentName(): ?string
    {
        return $this->studentName;
    }

    public function setStudentName(string $studentName): static
    {
        $this->studentName = $studentName;
        return $this;
    }

    public function getFormationName(): ?string
    {
        return $this->formationName;
    }

    public function setFormationName(string $formationName): static
    {
        $this->formationName = $formationName;
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

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): static
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    public function getReceiptContent(): ?string
    {
        return $this->receiptContent;
    }

    public function setReceiptContent(?string $receiptContent): static
    {
        $this->receiptContent = $receiptContent;
        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeInterface
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeInterface $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }

    public function getGeneratedBy(): ?User
    {
        return $this->generatedBy;
    }

    public function setGeneratedBy(?User $generatedBy): static
    {
        $this->generatedBy = $generatedBy;
        return $this;
    }

    public function getPrintedCount(): int
    {
        return $this->printedCount;
    }

    public function setPrintedCount(int $printedCount): static
    {
        $this->printedCount = $printedCount;
        return $this;
    }

    public function getLastPrintedAt(): ?\DateTimeInterface
    {
        return $this->lastPrintedAt;
    }

    public function setLastPrintedAt(?\DateTimeInterface $lastPrintedAt): static
    {
        $this->lastPrintedAt = $lastPrintedAt;
        return $this;
    }

    // ====================================================
    // MÉTHODES UTILITAIRES
    // ====================================================

    public function __toString(): string
    {
        return sprintf('Reçu %s - %s - %s DT',
            $this->receiptNumber ?: 'N/A',
            $this->studentName ?: 'N/A',
            $this->getFormattedAmount()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'receiptNumber' => $this->receiptNumber,
            'studentName' => $this->studentName,
            'formationName' => $this->formationName,
            'amount' => $this->amount,
            'formattedAmount' => $this->getFormattedAmount(),
            'paymentDate' => $this->paymentDate?->format('Y-m-d'),
            'paymentType' => $this->paymentType,
            'formattedPaymentType' => $this->getFormattedPaymentType(),
            'generatedAt' => $this->generatedAt?->format('Y-m-d H:i:s'),
            'generatedBy' => $this->generatedBy?->getFullName(),
            'printedCount' => $this->printedCount,
            'lastPrintedAt' => $this->lastPrintedAt?->format('Y-m-d H:i:s'),
            'hasPDF' => $this->hasPDF(),
            'isPrinted' => $this->isPrinted(),
        ];
    }

    public function getReceiptData(): array
    {
        return [
            'receiptNumber' => $this->receiptNumber,
            'studentName' => $this->studentName,
            'formationName' => $this->formationName,
            'amount' => $this->getFormattedAmount(),
            'paymentDate' => $this->paymentDate?->format('d/m/Y'),
            'paymentType' => $this->getFormattedPaymentType(),
            'generatedBy' => $this->generatedBy?->getFullName(),
            'generatedAt' => $this->generatedAt?->format('d/m/Y H:i'),
        ];
    }

    public function isRecentlyGenerated(): bool
    {
        if (!$this->generatedAt) {
            return false;
        }

        $oneHourAgo = new \DateTime('-1 hour');
        return $this->generatedAt > $oneHourAgo;
    }

    public function isRecentlyPrinted(): bool
    {
        if (!$this->lastPrintedAt) {
            return false;
        }

        $oneHourAgo = new \DateTime('-1 hour');
        return $this->lastPrintedAt > $oneHourAgo;
    }

    public function canBeDeleted(): bool
    {
        // Un reçu peut être supprimé s'il n'a jamais été imprimé
        // et qu'il a été généré il y a moins de 24h
        if ($this->printedCount > 0) {
            return false;
        }

        if (!$this->generatedAt) {
            return false;
        }

        $oneDayAgo = new \DateTime('-24 hours');
        return $this->generatedAt > $oneDayAgo;
    }

    public function getStatusBadge(): array
    {
        if (!$this->isPrinted()) {
            return [
                'class' => 'bg-yellow-100 text-yellow-800',
                'text' => 'Non imprimé'
            ];
        }

        if ($this->isRecentlyPrinted()) {
            return [
                'class' => 'bg-green-100 text-green-800',
                'text' => 'Récemment imprimé'
            ];
        }

        return [
            'class' => 'bg-blue-100 text-blue-800',
            'text' => "Imprimé {$this->printedCount} fois"
        ];
    }

    public function duplicate(): self
    {
        $duplicate = new self();
        $duplicate->setPayment($this->payment);
        $duplicate->setReceiptNumber($this->receiptNumber . '-COPY');
        $duplicate->setStudentName($this->studentName);
        $duplicate->setFormationName($this->formationName);
        $duplicate->setAmount($this->amount);
        $duplicate->setPaymentDate($this->paymentDate);
        $duplicate->setPaymentType($this->paymentType);
        $duplicate->setReceiptContent($this->receiptContent);
        $duplicate->setGeneratedBy($this->generatedBy);

        return $duplicate;
    }

    public function markAsViewed(): void
    {
        // Cette méthode peut être utilisée pour marquer un reçu comme "vu"
        // sans l'imprimer physiquement (consultation en ligne)
    }

    public function getFileSize(): ?string
    {
        if (!$this->hasPDF()) {
            return null;
        }

        $filePath = $this->getFullPdfPath();
        if (!file_exists($filePath)) {
            return null;
        }

        $bytes = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
