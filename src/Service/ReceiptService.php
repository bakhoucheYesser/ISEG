<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Receipt;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

class ReceiptService
{
    public function __construct(
        private Environment $twig,
        private Security $security,
        private string $receiptsDirectory
    ) {}

    public function generateReceipt(Payment $payment): Receipt
    {
        $user = $this->security->getUser();
        $enrollment = $payment->getEnrollment();

        $receipt = new Receipt();
        $receipt->setPayment($payment);
        $receipt->setReceiptNumber($payment->getReceiptNumber());
        $receipt->setStudentName($enrollment->getStudent()->getFullName());
        $receipt->setFormationName($enrollment->getFormation()->getName());
        $receipt->setAmount($payment->getAmount());
        $receipt->setPaymentDate($payment->getPaymentDate());
        $receipt->setPaymentType($payment->getPaymentType());
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

    public function generatePDF(Receipt $receipt): string
    {
        // Utiliser une bibliothèque comme TCPDF ou DomPDF
        // pour générer le PDF à partir du contenu HTML

        $filename = 'receipt_' . $receipt->getReceiptNumber() . '.pdf';
        $filepath = $this->receiptsDirectory . '/' . $filename;

        // Logic to generate PDF...
        // $pdf = new PDF();
        // $pdf->writeHTML($receipt->getReceiptContent());
        // $pdf->Output($filepath, 'F');

        $receipt->setPdfPath($filepath);

        return $filepath;
    }
}

