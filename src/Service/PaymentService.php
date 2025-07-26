<?php
namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Payment;
use App\Entity\PaymentInstallment;
use App\Entity\Receipt;
use App\Entity\User;
use App\Enum\PaymentType;
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
        PaymentType $paymentType,
        ?PaymentInstallment $installment = null,
        ?string $description = null,
        string $paymentMethod = 'CASH'
    ): Payment {
        $user = $this->security->getUser();

        if (!$user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        // Commencer une transaction
        $this->entityManager->beginTransaction();

        try {
            $payment = new Payment();
            $payment->setEnrollment($enrollment);
            $payment->setAmount((string) $amount);
            $payment->setPaymentType($paymentType);
            $payment->setPaymentMethod($paymentMethod);
            $payment->setCreatedBy($user);

            if ($installment) {
                $payment->setInstallment($installment);
                $installment->markAsPaid();
            }

            if ($description) {
                $payment->setDescription($description);
            }

            // Générer la référence
            $payment->generateReference();

            // Mettre à jour le montant payé dans l'inscription
            $enrollment->setTotalPaid(
                (string) ((float) $enrollment->getTotalPaid() + $amount)
            );
            $enrollment->updatePaymentStatus();

            $this->entityManager->persist($payment);
            $this->entityManager->flush(); // Flush pour obtenir l'ID du payment

            // Générer le reçu automatiquement
            $receipt = $this->receiptService->generateReceipt($payment);
            $this->entityManager->persist($receipt);

            // Générer le PDF (optionnel)
            $this->receiptService->generatePDF($receipt);

            $this->entityManager->flush();

            // Confirmer la transaction
            $this->entityManager->commit();

            return $payment;

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function validatePayment(Payment $payment, User $validator): void
    {
        if (!$payment->isActive()) {
            $payment->setIsActive(true);
            $payment->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();
        }
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

    public function getPaymentStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->where('p.paymentDate BETWEEN :start AND :end')
            ->andWhere('p.isActive = true')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        $payments = $qb->getQuery()->getResult();

        $totalAmount = 0;
        $paymentsByType = [];
        $paymentsByMethod = [];

        foreach ($payments as $payment) {
            $totalAmount += (float) $payment->getAmount();

            $type = $payment->getPaymentType()->value;
            $paymentsByType[$type] = ($paymentsByType[$type] ?? 0) + (float) $payment->getAmount();

            $method = $payment->getPaymentMethod();
            $paymentsByMethod[$method] = ($paymentsByMethod[$method] ?? 0) + (float) $payment->getAmount();
        }

        return [
            'totalAmount' => $totalAmount,
            'totalCount' => count($payments),
            'paymentsByType' => $paymentsByType,
            'paymentsByMethod' => $paymentsByMethod,
            'averageAmount' => count($payments) > 0 ? $totalAmount / count($payments) : 0,
        ];
    }
}
