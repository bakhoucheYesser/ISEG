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
use Psr\Log\LoggerInterface;
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
        private StudentService $studentService,
        private LoggerInterface $logger
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

        // Clone the query builder for count query BEFORE adding ORDER BY
        $countQb = clone $qb;

        // Add ORDER BY only to the main query
        $qb->orderBy('s.lastName', 'ASC')
            ->addOrderBy('s.firstName', 'ASC');

        // Pagination
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $enrollments = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Use the cloned query builder for count (without ORDER BY)
        $total = $countQb->select('COUNT(e.id)')
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
        // Debug: Vérifier que l'étudiant existe
        $this->logger->info('Starting enrollment for student', [
            'student_id' => $student->getId(),
            'student_name' => $student->getFullName()
        ]);

        $enrollment = new Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setCreatedBy($this->getUser());


        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->logger->info('Form submitted', [
                'is_valid' => $form->isValid(),
                'errors' => $form->getErrors(true, false)
            ]);

            // Debug: Afficher les erreurs de validation
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $this->logger->error('Form validation error', [
                        'message' => $error->getMessage(),
                        'cause' => $error->getCause()
                    ]);
                    $this->addFlash('error', 'Erreur de validation: ' . $error->getMessage());
                }

                return $this->render('student/enroll.html.twig', [
                    'student' => $student,
                    'enrollment' => $enrollment,
                    'form' => $form,
                ]);
            }

            try {
                // Debug: Vérifier les données du formulaire avant l'enregistrement
                $this->logger->info('Form data before service call', [
                    'formation_id' => $enrollment->getFormation()?->getId(),
                    'classroom_id' => $enrollment->getClassRoom()?->getId(),
                    'payment_mode_id' => $enrollment->getPaymentMode()?->getId(),
                    'academic_year' => $enrollment->getAcademicYear()
                ]);

                // Vérifier que toutes les entités nécessaires sont présentes
                if (!$enrollment->getFormation()) {
                    throw new \Exception('Formation non sélectionnée');
                }
                if (!$enrollment->getClassRoom()) {
                    throw new \Exception('Classe non sélectionnée');
                }
                if (!$enrollment->getPaymentMode()) {
                    throw new \Exception('Mode de paiement non sélectionné');
                }
                if (!$enrollment->getAcademicYear()) {
                    throw new \Exception('Année académique non définie');
                }

                $enrollment = $this->studentService->registerStudent(
                    $student,
                    $enrollment->getFormation(),
                    $enrollment->getClassRoom(),
                    $enrollment->getPaymentMode(),
                    $enrollment->getAcademicYear()
                );

                $this->logger->info('Student enrolled successfully', [
                    'enrollment_id' => $enrollment->getId()
                ]);

                $this->addFlash('success', 'Étudiant inscrit avec succès !');
                return $this->redirectToRoute('student_show', ['id' => $student->getId()]);

            } catch (\Exception $e) {
                $this->logger->error('Enrollment error', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
                'students' => array_map(function($student) {
                    // Handle both Student entities and Enrollment entities
                    if ($student instanceof Enrollment) {
                        $studentEntity = $student->getStudent();
                        return [
                            'id' => $studentEntity->getId(),
                            'fullName' => $studentEntity->getFullName(),
                            'cin' => $studentEntity->getCin(),
                            'formation' => $student->getFormation()->getName(),
                            'paymentStatus' => $student->getPaymentStatus()->value,
                        ];
                    } else {
                        // Direct Student entity
                        return [
                            'id' => $student->getId(),
                            'fullName' => $student->getFullName(),
                            'cin' => $student->getCin(),
                            'formation' => null,
                            'paymentStatus' => null,
                        ];
                    }
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
