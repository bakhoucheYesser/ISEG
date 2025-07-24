<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Enrollment;
use App\Service\PaymentService;
use App\Service\ReceiptService;
use App\Form\PaymentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payments')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaymentService $paymentService,
        private ReceiptService $receiptService
    ) {}

    #[Route('/', name: 'payment_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', '');
        $dateRange = $request->query->get('date_range', '');

        $qb = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->join('p.enrollment', 'e')
            ->join('e.student', 's');

        // Filtres
        if ($status === 'validated') {
            $qb->andWhere('p.isValidated = true');
        } elseif ($status === 'pending') {
            $qb->andWhere('p.isValidated = false');
        }

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $qb->andWhere('p.paymentDate = :today')
                        ->setParameter('today', new \DateTime('today'));
                    break;
                case 'week':
                    $qb->andWhere('p.paymentDate >= :weekStart')
                        ->setParameter('weekStart', new \DateTime('-7 days'));
                    break;
                case 'month':
                    $qb->andWhere('p.paymentDate >= :monthStart')
                        ->setParameter('monthStart', new \DateTime('first day of this month'));
                    break;
            }
        }

        $qb->orderBy('p.createdAt', 'DESC');

        // Pagination
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $payments = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $qb->select('COUNT(p.id)')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'status' => $status,
            'date_range' => $dateRange,
            'total' => $total,
        ]);
    }

    #[Route('/pending', name: 'payment_pending')]
    public function pending(): Response
    {
        $pendingPayments = $this->entityManager->getRepository(Payment::class)
            ->findBy(['isActive' => false], ['createdAt' => 'ASC']);

        return $this->render('payment/pending.html.twig', [
            'payments' => $pendingPayments,
        ]);
    }

    #[Route('/enrollment/{id}/new', name: 'payment_new', requirements: ['id' => '\d+'])]
    public function new(Request $request, Enrollment $enrollment): Response
    {
        $payment = new Payment();
        $payment->setEnrollment($enrollment);

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $payment = $this->paymentService->processPayment(
                    $enrollment,
                    (float) $payment->getAmount(),
                    $payment->getPaymentType(),
                    null,
                    $payment->getDescription()
                );

                $this->addFlash('success', 'Paiement enregistré avec succès ! Reçu généré.');
                return $this->redirectToRoute('payment_show', ['id' => $payment->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du paiement : ' . $e->getMessage());
            }
        }

        return $this->render('payment/new.html.twig', [
            'enrollment' => $enrollment,
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'payment_show', requirements: ['id' => '\d+'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/validate', name: 'payment_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function validate(Request $request, Payment $payment): Response
    {
        if ($this->isCsrfTokenValid('validate'.$payment->getId(), $request->request->get('_token'))) {
            try {
                $this->paymentService->validatePayment($payment, $this->getUser());
                $this->addFlash('success', 'Paiement validé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la validation : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/{id}/receipt', name: 'payment_receipt', requirements: ['id' => '\d+'])]
    public function receipt(Payment $payment): Response
    {
        $receipt = $payment->getReceipt();

        if (!$receipt) {
            $this->addFlash('error', 'Aucun reçu trouvé pour ce paiement.');
            return $this->redirectToRoute('payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/receipt.html.twig', [
            'receipt' => $receipt,
            'payment' => $payment,
        ]);
    }
}
