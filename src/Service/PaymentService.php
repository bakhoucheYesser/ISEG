<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Payment;
use App\Entity\PaymentInstallment;
use App\Entity\Receipt;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private ReceiptService $receiptService
    ) {}

    public function processPayment(
        Enrollment $enrollment,
        float $amount,
        string $paymentType,
        ?PaymentInstallment $installment = null,
        ?string $description = null
    ): Payment {
        $user = $this->security->getUser();

        $payment = new Payment();
        $payment->setEnrollment($enrollment);
        $payment->setAmount((string) $amount);
        $payment->setPaymentType($paymentType);
        $payment->setCreatedBy($user);

        if ($installment) {
            $payment->setInstallment($installment);
            $installment->markAsPaid();
        }

        if ($description) {
            $payment->setDescription($description);
        }

        // Mettre à jour le montant payé dans l'inscription
        $enrollment->setTotalPaid(
            (string) ((float) $enrollment->getTotalPaid() + $amount)
        );
        $enrollment->updatePaymentStatus();

        $this->entityManager->persist($payment);

        // Générer le reçu automatiquement
        $receipt = $this->receiptService->generateReceipt($payment);
        $this->entityManager->persist($receipt);

        $this->entityManager->flush();

        return $payment;
    }

    public function validatePayment(Payment $payment, User $validator): void
    {
        $payment->validate($validator);
        $this->entityManager->flush();
    }

    public function generateInstallments(Enrollment $enrollment): array
    {
        $paymentMode = $enrollment->getPaymentMode();
        $formationFees = (float) $enrollment->getFormationFees();

        $installmentData = $paymentMode->calculateInstallments($formationFees);
        $installments = [];

        foreach ($installmentData as $data) {
            $installment = new PaymentInstallment();
            $installment->setEnrollment($enrollment);
            $installment->setInstallmentNumber($data['number']);
            $installment->setAmount((string) $data['amount']);
            $installment->setDueDate($data['due_date']);

            $this->entityManager->persist($installment);
            $installments[] = $installment;
        }

        $this->entityManager->flush();

        return $installments;
    }

    public function getOverdueInstallments(): array
    {
        return $this->entityManager->getRepository(PaymentInstallment::class)
            ->createQueryBuilder('pi')
            ->where('pi.status = :pending')
            ->andWhere('pi.dueDate < :today')
            ->setParameter('pending', PaymentInstallment::STATUS_PENDING)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
