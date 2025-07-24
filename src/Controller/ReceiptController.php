<?php

namespace App\Controller;

use App\Entity\Receipt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/receipts')]
#[IsGranted('ROLE_USER')]
class ReceiptController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'receipt_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');

        $qb = $this->entityManager->getRepository(Receipt::class)
            ->createQueryBuilder('r')
            ->join('r.payment', 'p')
            ->join('p.enrollment', 'e')
            ->join('e.student', 's');

        if ($search) {
            $qb->andWhere('r.receiptNumber LIKE :search OR s.firstName LIKE :search OR s.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('r.generatedAt', 'DESC');

        // Pagination
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $receipts = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $qb->select('COUNT(r.id)')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('receipt/index.html.twig', [
            'receipts' => $receipts,
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'search' => $search,
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'receipt_show', requirements: ['id' => '\d+'])]
    public function show(Receipt $receipt): Response
    {
        // Marquer comme imprimé
        $receipt->print();
        $this->entityManager->flush();

        return $this->render('receipt/show.html.twig', [
            'receipt' => $receipt,
        ]);
    }
}
