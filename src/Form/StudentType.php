<?php

// src/Form/StudentType.php
namespace App\Form;

use App\Entity\Student;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cin', TextType::class, [
                'label' => 'Numéro CIN',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '12345678',
                    'maxlength' => '8',
                    'pattern' => '[0-9]{8}'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le CIN est obligatoire'),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Le CIN doit contenir exactement 8 chiffres'
                    ])
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ahmed'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le prénom est obligatoire'),
                    new Assert\Length(min: 2, max: 100)
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ben Ali'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est obligatoire'),
                    new Assert\Length(min: 2, max: 100)
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'ahmed.benali@email.com'
                ],
                'constraints' => [
                    new Assert\Email(message: 'Veuillez saisir un email valide')
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '+216 20 123 456'
                ]
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => 'form-textarea',
                    'rows' => 3,
                    'placeholder' => 'Adresse complète...'
                ]
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input'
                ]
            ])
            ->add('placeOfBirth', TextType::class, [
                'label' => 'Lieu de naissance',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Tunis'
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'required' => false,
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Choisir...'
            ])
            ->add('nationality', TextType::class, [
                'label' => 'Nationalité',
                'data' => 'Tunisienne',
                'attr' => [
                    'class' => 'form-input'
                ]
            ])
            ->add('emergencyContactName', TextType::class, [
                'label' => 'Contact d\'urgence (Nom)',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Nom du contact d\'urgence'
                ]
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => 'Contact d\'urgence (Téléphone)',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '+216 20 123 456'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes administratives',
                'required' => false,
                'attr' => [
                    'class' => 'form-textarea',
                    'rows' => 3,
                    'placeholder' => 'Notes internes...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
        ]);
    }
}
