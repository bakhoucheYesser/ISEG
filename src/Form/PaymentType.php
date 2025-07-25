<?php

namespace App\Form;

use App\Entity\Payment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Montant (DT)',
                'currency' => 'TND',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '0.00',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le montant est obligatoire'),
                    new Assert\Positive(message: 'Le montant doit être positif')
                ]
            ])
            ->add('paymentDate', DateType::class, [
                'label' => 'Date de paiement',
                'data' => new \DateTime(),
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input'
                ],
                'constraints' => [
                    new Assert\NotNull(message: 'La date est obligatoire')
                ]
            ])
            ->add('paymentDate', TimeType::class, [
                'label' => 'Heure de paiement',
                'data' => new \DateTime(),
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input'
                ]
            ])
            ->add('paymentType', ChoiceType::class, [
                'label' => 'Type de paiement',
                'choices' => [
                    'Frais d\'inscription' => Payment::TYPE_REGISTRATION,
                    'Frais de formation' => Payment::TYPE_FORMATION,
                    'Paiement partiel' => Payment::TYPE_PARTIAL,
                    'Paiement complet' => Payment::TYPE_FULL
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le type de paiement est obligatoire')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-textarea',
                    'rows' => 3,
                    'placeholder' => 'Description du paiement...'
                ]
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
