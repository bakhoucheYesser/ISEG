<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Receipt;
use App\Enum\PaymentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

class ReceiptService
{
    public function __construct(
        private Environment $twig,
        private Security $security,
        private EntityManagerInterface $entityManager,
        private string $receiptsDirectory = 'var/receipts'
    ) {}

    public function generateReceipt(Payment $payment): Receipt
    {
        $user = $this->security->getUser();
        $enrollment = $payment->getEnrollment();

        $receipt = new Receipt();
        $receipt->setPayment($payment);
        $receipt->setReceiptNumber($this->generateReceiptNumber());
        $receipt->setStudentName($enrollment->getStudent()->getFullName());
        $receipt->setFormationName($enrollment->getFormation()->getName());
        $receipt->setAmount($payment->getAmount());
        $receipt->setPaymentDate($payment->getPaymentDate());

        // CORRECTION: Convertir l'enum PaymentType en string
        $receipt->setPaymentType($payment->getPaymentType()->value);

        $receipt->setGeneratedBy($user);

        // Générer le contenu HTML du reçu
        $receiptContent = $this->twig->render('receipt/template.html.twig', [
            'receipt' => $receipt,
            'payment' => $payment,
            'enrollment' => $enrollment,
            'student' => $enrollment->getStudent(),
            'formation' => $enrollment->getFormation(),
        ]);

        $receipt->setReceiptContent($receiptContent);

        return $receipt;
    }

    public function generateReceiptNumber(): string
    {
        // Générer un numéro de reçu unique
        $year = date('Y');
        $lastReceipt = $this->entityManager->getRepository(Receipt::class)
            ->createQueryBuilder('r')
            ->where('r.receiptNumber LIKE :pattern')
            ->setParameter('pattern', 'REC-' . $year . '-%')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastReceipt) {
            // Extraire le numéro séquentiel du dernier reçu
            $lastNumber = (int) substr($lastReceipt->getReceiptNumber(), -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('REC-%s-%06d', $year, $nextNumber);
    }

    public function generatePDF(Receipt $receipt): string
    {
        // Créer le répertoire s'il n'existe pas
        $directory = $this->getReceiptsDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'receipt_' . $receipt->getReceiptNumber() . '.pdf';
        $filepath = $directory . '/' . $filename;

        // Exemple avec TCPDF (vous devez installer tcpdf/tcpdf)
        /*
        $pdf = new \TCPDF();
        $pdf->AddPage();
        $pdf->writeHTML($receipt->getReceiptContent());
        $pdf->Output($filepath, 'F');
        */


        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($receipt->getReceiptContent());

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($filepath, $dompdf->output());


        // Pour le moment, simuler la génération
        file_put_contents($filepath, $receipt->getReceiptContent());

        $receipt->setPdfPath('/receipts/' . $filename);

        return $filepath;
    }

    private function getReceiptsDirectory(): string
    {
        $projectDir = $_ENV['KERNEL_PROJECT_DIR'] ?? getcwd();
        return $projectDir . '/' . ltrim($this->receiptsDirectory, '/');
    }

    public function printReceipt(Receipt $receipt): void
    {
        $receipt->print();
        $this->entityManager->flush();
    }

    public function getReceiptsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->entityManager->getRepository(Receipt::class)
            ->createQueryBuilder('r')
            ->where('r.generatedAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('r.generatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getReceiptsByStudent(int $studentId): array
    {
        return $this->entityManager->getRepository(Receipt::class)
            ->createQueryBuilder('r')
            ->join('r.payment', 'p')
            ->join('p.enrollment', 'e')
            ->join('e.student', 's')
            ->where('s.id = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('r.generatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
