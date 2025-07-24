<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/statistics')]
#[IsGranted('ROLE_USER')]
class StatisticsController extends AbstractController
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {}

    #[Route('/', name: 'statistics')]
    public function index(): Response
    {
        $paymentRate = $this->statisticsService->calculatePaymentRate();
        $studentsByLevel = $this->statisticsService->getStudentsByLevel();
        $paymentStats = $this->statisticsService->getPaymentStatistics();

        return $this->render('statistics/index.html.twig', [
            'payment_rate' => $paymentRate,
            'students_by_level' => $studentsByLevel,
            'payment_statistics' => $paymentStats,
        ]);
    }
}
