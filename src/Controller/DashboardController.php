<?php

namespace App\Controller;

use App\Service\StatisticsService;
use App\Service\StudentService;
use App\Entity\Enrollment;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatisticsService $statisticsService,
        private StudentService $studentService
    ) {}

    #[Route('/', name: 'dashboard')]
    #[Route('/dashboard', name: 'dashboard_alt')]
    public function index(): Response
    {
        // Statistiques principales
        $totalStudents = $this->entityManager->getRepository(Enrollment::class)
            ->count(['isActive' => true]);

        $pendingPayments = $this->entityManager->getRepository(Payment::class)
            ->count(['isActive' => false]);

        $paymentRate = $this->statisticsService->calculatePaymentRate();
        $studentsByLevel = $this->statisticsService->getStudentsByLevel();

        // Étudiants récents
        $recentStudents = $this->entityManager->getRepository(Enrollment::class)
            ->findBy(
                ['isActive' => true],
                ['createdAt' => 'DESC'],
                5
            );

        // Paiements récents (actifs seulement)
        $recentPayments = $this->entityManager->getRepository(Payment::class)
            ->findBy(
                ['isActive' => true],
                ['createdAt' => 'DESC'],
                5
            );

        // Paiements en attente (non actifs)
        $pendingValidationPayments = $this->entityManager->getRepository(Payment::class)
            ->findBy(
                ['isActive' => false],
                ['createdAt' => 'ASC'],
                10
            );

        // Revenue du mois (paiements actifs seulement)
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        $monthlyRevenue = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.paymentDate BETWEEN :start AND :end')
            ->andWhere('p.isActive = true')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'total_students' => $totalStudents,
                'pending_payments' => $pendingPayments,
                'payment_rate' => $paymentRate,
                'monthly_revenue' => $monthlyRevenue,
            ],
            'students_by_level' => $studentsByLevel,
            'recent_students' => $recentStudents,
            'recent_payments' => $recentPayments,
            'pending_validation_payments' => $pendingValidationPayments,
        ]);
    }

    #[Route('/dashboard/stats', name: 'dashboard_stats', methods: ['GET'])]
    public function getStats(): Response
    {
        // Calculer le revenu mensuel
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        $monthlyRevenue = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.paymentDate BETWEEN :start AND :end')
            ->andWhere('p.isActive = true')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // API endpoint pour mise à jour AJAX des statistiques
        $stats = [
            'totalStudents' => $this->entityManager->getRepository(Enrollment::class)
                ->count(['isActive' => true]),
            'pendingPayments' => $this->entityManager->getRepository(Payment::class)
                ->count(['isActive' => false]),
            'paymentRate' => $this->statisticsService->calculatePaymentRate(),
            'monthlyRevenue' => $monthlyRevenue,
        ];

        return $this->json($stats);
    }
}
