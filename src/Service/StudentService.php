<?php

namespace App\Service;

use App\Entity\Student;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\ClassRoom;
use App\Entity\PaymentMode;
use App\Enum\PaymentStatus;
use App\Enum\RegistrationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class StudentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private LoggerInterface $logger
    ) {}

    public function registerStudent(
        Student $student,
        Formation $formation,
        ClassRoom $classRoom,
        PaymentMode $paymentMode,
        string $academicYear
    ): Enrollment {

        $this->logger->info('Starting student registration', [
            'student_id' => $student->getId(),
            'formation_id' => $formation->getId(),
            'classroom_id' => $classRoom->getId(),
            'payment_mode_id' => $paymentMode->getId(),
            'academic_year' => $academicYear
        ]);

        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        // Vérifier si l'étudiant n'est pas déjà inscrit à cette formation pour cette année
        $existingEnrollment = $this->entityManager->getRepository(Enrollment::class)
            ->findOneBy([
                'student' => $student,
                'formation' => $formation,
                'academicYear' => $academicYear,
                'isActive' => true
            ]);

        if ($existingEnrollment) {
            throw new \Exception('L\'étudiant est déjà inscrit à cette formation pour cette année académique');
        }

        // Vérifier si la classe a de la place
        if ($classRoom->isFull()) {
            throw new \Exception('La classe est complète');
        }

        // Commencer une transaction
        $this->entityManager->beginTransaction();

        try {
            $enrollment = new Enrollment();
            $enrollment->setStudent($student);
            $enrollment->setFormation($formation);
            $enrollment->setClassRoom($classRoom);
            $enrollment->setPaymentMode($paymentMode);
            $enrollment->setAcademicYear($academicYear);
            $enrollment->setCreatedBy($user);

            // Définir les statuts par défaut
            $enrollment->setRegistrationStatus(RegistrationStatus::PENDING);
            $enrollment->setPaymentStatus(PaymentStatus::NOT_PAID);

            // Calculer les montants
            $registrationFees = (float) $formation->getRegistrationFees();
            $formationFees = (float) $formation->getTotalPrice();
            $totalAmount = $registrationFees + $formationFees;

            $this->logger->info('Calculated amounts', [
                'registration_fees' => $registrationFees,
                'formation_fees' => $formationFees,
                'total_amount' => $totalAmount
            ]);

            $enrollment->setRegistrationFees((string) $registrationFees);
            $enrollment->setFormationFees((string) $formationFees);
            $enrollment->setTotalAmount((string) $totalAmount);
            $enrollment->setRemainingAmount((string) $totalAmount);

            // Mettre à jour le statut de paiement
            $enrollment->updatePaymentStatus();

            // Ajouter l'étudiant à la classe
            $classRoom->addStudent();

            // Persister les entités
            $this->entityManager->persist($enrollment);
            $this->entityManager->flush();

            // Confirmer la transaction
            $this->entityManager->commit();

            $this->logger->info('Student registration completed successfully', [
                'enrollment_id' => $enrollment->getId()
            ]);

            return $enrollment;

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->entityManager->rollback();

            $this->logger->error('Student registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function searchStudents(string $query): array
    {
        return $this->entityManager->getRepository(Student::class)
            ->createQueryBuilder('s')
            ->where('s.cin LIKE :query')
            ->orWhere('s.firstName LIKE :query')
            ->orWhere('s.lastName LIKE :query')
            ->orWhere("CONCAT(s.firstName, ' ', s.lastName) LIKE :query")
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.lastName', 'ASC')
            ->addOrderBy('s.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStudentsByPaymentStatus(string $status): array
    {
        return $this->entityManager->getRepository(Enrollment::class)
            ->createQueryBuilder('e')
            ->join('e.student', 's')
            ->where('e.paymentStatus = :status')
            ->andWhere('e.isActive = true')
            ->setParameter('status', $status)
            ->orderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStudentsByLevel(string $levelCode): array
    {
        return $this->entityManager->getRepository(Student::class)
            ->createQueryBuilder('s')
            ->join('s.enrollments', 'e')
            ->join('e.formation', 'f')
            ->join('f.academicLevel', 'al')
            ->where('al.code = :levelCode')
            ->andWhere('e.isActive = true')
            ->setParameter('levelCode', $levelCode)
            ->orderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
