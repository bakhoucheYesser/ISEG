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
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private ReceiptService $receiptService,
        private LoggerInterface $logger
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

        $this->logger->info('Processing payment', [
            'enrollment_id' => $enrollment->getId(),
            'amount' => $amount,
            'payment_type' => $paymentType->value,
            'payment_method' => $paymentMethod,
            'user_id' => $user->getId()
        ]);

        // Validation des montants
        if ($amount <= 0) {
            throw new \Exception('Le montant doit être positif');
        }

        $remainingAmount = (float) $enrollment->getRemainingAmount();
        if ($amount > $remainingAmount) {
            throw new \Exception(sprintf(
                'Le montant payé (%.2f DT) ne peut pas dépasser le montant restant (%.2f DT)',
                $amount,
                $remainingAmount
            ));
        }

        // Commencer une transaction
        $this->entityManager->beginTransaction();

        try {
            // Créer le paiement
            $payment = new Payment();
            $payment->setEnrollment($enrollment);
            $payment->setAmount((string) $amount);
            $payment->setPaymentType($paymentType);
            $payment->setPaymentMethod($paymentMethod);
            $payment->setCreatedBy($user);
            $payment->setPaymentDate(new \DateTime());

            if ($installment) {
                $payment->setInstallment($installment);
                $installment->markAsPaid();
            }

            if ($description) {
                $payment->setDescription($description);
            }

            // Persister le paiement d'abord pour obtenir un ID
            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            // Générer la référence après avoir l'ID
            $reference = $this->generatePaymentReference($payment);
            $payment->setReference($reference);

            // Mettre à jour le montant payé dans l'inscription
            $newTotalPaid = (float) $enrollment->getTotalPaid() + $amount;
            $enrollment->setTotalPaid((string) $newTotalPaid);
            $enrollment->updatePaymentStatus();

            $this->logger->info('Payment amounts updated', [
                'old_total_paid' => $enrollment->getTotalPaid(),
                'new_total_paid' => $newTotalPaid,
                'remaining_amount' => $enrollment->getRemainingAmount()
            ]);

            // Générer le reçu automatiquement
            try {
                $receipt = $this->receiptService->generateReceipt($payment);
                $this->entityManager->persist($receipt);

                $this->logger->info('Receipt generated', [
                    'payment_id' => $payment->getId(),
                    'receipt_number' => $receipt->getReceiptNumber()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to generate receipt', [
                    'payment_id' => $payment->getId(),
                    'error' => $e->getMessage()
                ]);
                // Ne pas faire échouer le paiement si la génération du reçu échoue
            }

            $this->entityManager->flush();

            // Confirmer la transaction
            $this->entityManager->commit();

            $this->logger->info('Payment processed successfully', [
                'payment_id' => $payment->getId(),
                'reference' => $payment->getReference()
            ]);

            return $payment;

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->entityManager->rollback();

            $this->logger->error('Payment processing failed', [
                'error' => $e->getMessage(),
                'enrollment_id' => $enrollment->getId(),
                'amount' => $amount
            ]);

            throw $e;
        }
    }

    private function generatePaymentReference(Payment $payment): string
    {
        $year = date('Y');
        $paddedId = str_pad((string) $payment->getId(), 6, '0', STR_PAD_LEFT);
        return sprintf('PAY-%s-%s', $year, $paddedId);
    }

    public function validatePayment(Payment $payment, User $validator): void
    {
        if (!$payment->isActive()) {
            $this->logger->info('Validating payment', [
                'payment_id' => $payment->getId(),
                'validator_id' => $validator->getId()
            ]);

            $payment->setIsActive(true);
            $payment->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            $this->logger->info('Payment validated successfully', [
                'payment_id' => $payment->getId()
            ]);
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
