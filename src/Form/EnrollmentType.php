<?php

namespace App\Form;

use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\ClassRoom;
use App\Entity\PaymentMode;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EnrollmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formation', EntityType::class, [
                'class' => Formation::class,
                'choice_label' => 'name',
                'label' => 'Formation',
                'attr' => [
                    'class' => 'form-select'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('f')
                        ->where('f.isActive = true')
                        ->orderBy('f.name', 'ASC');
                },
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez choisir une formation')
                ]
            ])
            ->add('classRoom', EntityType::class, [
                'class' => ClassRoom::class,
                'choice_label' => 'name',
                'label' => 'Classe',
                'attr' => [
                    'class' => 'form-select'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isActive = true')
                        ->orderBy('c.name', 'ASC');
                },
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez choisir une classe')
                ]
            ])
            ->add('paymentMode', EntityType::class, [
                'class' => PaymentMode::class,
                'choice_label' => 'name',
                'label' => 'Mode de paiement',
                'attr' => [
                    'class' => 'form-select'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pm')
                        ->where('pm.isActive = true')
                        ->orderBy('pm.name', 'ASC');
                },
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez choisir un mode de paiement')
                ]
            ])
            ->add('academicYear', TextType::class, [
                'label' => 'Année académique',
                'data' => date('Y') . '-' . (date('Y') + 1),
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '2024-2025'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'année académique est obligatoire'),
                    new Assert\Regex([
                        'pattern' => '/^\d{4}-\d{4}$/',
                        'message' => 'Format attendu: YYYY-YYYY'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enrollment::class,
        ]);
    }
}
