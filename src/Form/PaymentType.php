<?php
namespace App\Form;

use App\Entity\Payment;
use App\Enum\PaymentType as PaymentTypeEnum; // Renommage pour éviter le conflit
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'TND',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le montant est obligatoire'),
                    new Assert\Positive(message: 'Le montant doit être positif')
                ]
            ])
            ->add('paymentDate', DateTimeType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'data' => new \DateTime(), // Valeur par défaut : maintenant
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de paiement est obligatoire')
                ]
            ])
            ->add('paymentType', ChoiceType::class, [
                'label' => 'Type de paiement',
                'choices' => [
                    'Frais d\'inscription' => PaymentTypeEnum::REGISTRATION,
                    'Frais de formation' => PaymentTypeEnum::FORMATION,
                    'Paiement partiel' => PaymentTypeEnum::PARTIAL,
                    'Paiement complet' => PaymentTypeEnum::FULL,
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Sélectionnez le type de paiement',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le type de paiement est obligatoire')
                ]
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Méthode de paiement',
                'choices' => [
                    'Espèces' => 'CASH',
                    'Chèque' => 'CHECK',
                    'Virement bancaire' => 'BANK_TRANSFER',
                    'Carte de crédit' => 'CREDIT_CARD',
                    'Paiement en ligne' => 'ONLINE',
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Sélectionnez la méthode de paiement',
                'data' => 'CASH', // Valeur par défaut
                'constraints' => [
                    new Assert\NotBlank(message: 'La méthode de paiement est obligatoire')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description ou notes sur le paiement'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
