<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\ClassRoom;
use App\Entity\PaymentMode;
use App\Service\StudentService;
use App\Form\StudentType;
use App\Form\EnrollmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/students')]
#[IsGranted('ROLE_USER')]
class StudentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StudentService $studentService
    ) {}

    #[Route('/', name: 'student_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $level = $request->query->get('level', '');
        $paymentStatus = $request->query->get('payment_status', '');

        $qb = $this->entityManager->getRepository(Enrollment::class)
            ->createQueryBuilder('e')
            ->join('e.student', 's')
            ->join('e.formation', 'f')
            ->join('f.academicLevel', 'al')
            ->where('e.isActive = true');

        // Filtres
        if ($search) {
            $qb->andWhere('s.cin LIKE :search OR s.firstName LIKE :search OR s.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($level) {
            $qb->andWhere('al.code = :level')
                ->setParameter('level', $level);
        }

        if ($paymentStatus) {
            $qb->andWhere('e.paymentStatus = :paymentStatus')
                ->setParameter('paymentStatus', $paymentStatus);
        }

        $qb->orderBy('s.lastName', 'ASC')
            ->addOrderBy('s.firstName', 'ASC');

        // Pagination
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $enrollments = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $qb->select('COUNT(e.id)')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        // Données pour les filtres
        $levels = $this->entityManager->getRepository(\App\Entity\AcademicLevel::class)
            ->findBy(['isActive' => true], ['code' => 'ASC']);

        return $this->render('student/index.html.twig', [
            'enrollments' => $enrollments,
            'levels' => $levels,
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'search' => $search,
            'level' => $level,
            'payment_status' => $paymentStatus,
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'student_new')]
    public function new(Request $request): Response
    {
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($student);
                $this->entityManager->flush();

                $this->addFlash('success', 'Étudiant créé avec succès !');
                return $this->redirectToRoute('student_enroll', ['id' => $student->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'student_show', requirements: ['id' => '\d+'])]
    public function show(Student $student): Response
    {
        $enrollments = $this->entityManager->getRepository(Enrollment::class)
            ->findBy(['student' => $student], ['createdAt' => 'DESC']);

        return $this->render('student/show.html.twig', [
            'student' => $student,
            'enrollments' => $enrollments,
        ]);
    }

    #[Route('/{id}/edit', name: 'student_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Student $student): Response
    {
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();
                $this->addFlash('success', 'Étudiant modifié avec succès !');
                return $this->redirectToRoute('student_show', ['id' => $student->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/enroll', name: 'student_enroll', requirements: ['id' => '\d+'])]
    public function enroll(Request $request, Student $student): Response
    {
        $enrollment = new Enrollment();
        $enrollment->setStudent($student);

        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $enrollment = $this->studentService->registerStudent(
                    $student,
                    $enrollment->getFormation(),
                    $enrollment->getClassRoom(),
                    $enrollment->getPaymentMode(),
                    $enrollment->getAcademicYear()
                );

                $this->addFlash('success', 'Étudiant inscrit avec succès !');
                return $this->redirectToRoute('student_show', ['id' => $student->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());
            }
        }

        return $this->render('student/enroll.html.twig', [
            'student' => $student,
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/search', name: 'student_search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $students = [];

        if (strlen($query) >= 2) {
            $students = $this->studentService->searchStudents($query);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'students' => array_map(function($enrollment) {
                    return [
                        'id' => $enrollment->getStudent()->getId(),
                        'fullName' => $enrollment->getStudent()->getFullName(),
                        'cin' => $enrollment->getStudent()->getCin(),
                        'formation' => $enrollment->getFormation()->getName(),
                        'paymentStatus' => $enrollment->getPaymentStatus(),
                    ];
                }, $students)
            ]);
        }

        return $this->render('student/search.html.twig', [
            'query' => $query,
            'students' => $students,
        ]);
    }

    #[Route('/{id}/delete', name: 'student_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Student $student): Response
    {
        if ($this->isCsrfTokenValid('delete'.$student->getId(), $request->request->get('_token'))) {
            try {
                // Soft delete - marquer comme inactif au lieu de supprimer
                $student->setIsActive(false);
                $this->entityManager->flush();

                $this->addFlash('success', 'Étudiant supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('student_index');
    }
}
