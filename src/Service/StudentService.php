<?php

namespace App\Service;

use App\Entity\Student;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\ClassRoom;
use App\Entity\PaymentMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class StudentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function registerStudent(
        Student $student,
        Formation $formation,
        ClassRoom $classRoom,
        PaymentMode $paymentMode,
        string $academicYear
    ): Enrollment {
        $user = $this->security->getUser();

        // Vérifier si la classe a de la place
        if ($classRoom->isFull()) {
            throw new \Exception('La classe est complète');
        }

        $enrollment = new Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setFormation($formation);
        $enrollment->setClassRoom($classRoom);
        $enrollment->setPaymentMode($paymentMode);
        $enrollment->setAcademicYear($academicYear);
        $enrollment->setCreatedBy($user);

        // Calculer les montants
        $registrationFees = (float) $formation->getRegistrationFees();
        $formationFees = (float) $formation->getTotalPrice();
        $totalAmount = $registrationFees + $formationFees;

        $enrollment->setRegistrationFees((string) $registrationFees);
        $enrollment->setFormationFees((string) $formationFees);
        $enrollment->setTotalAmount((string) $totalAmount);
        $enrollment->setRemainingAmount((string) $totalAmount);

        // Ajouter l'étudiant à la classe
        $classRoom->addStudent();

        $this->entityManager->persist($student);
        $this->entityManager->persist($enrollment);
        $this->entityManager->flush();

        return $enrollment;
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
