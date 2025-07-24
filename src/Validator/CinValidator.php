<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CinValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // Validation du format CIN tunisien (8 chiffres)
        if (!preg_match('/^[0-9]{8}$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // Validation de la somme de contrôle (optionnel pour CIN tunisien)
        $this->validateChecksum($value, $constraint);
    }

    private function validateChecksum(string $cin, Constraint $constraint): void
    {
        // Implémentation de la validation de la somme de contrôle du CIN tunisien
        // selon l'algorithme officiel
        $digits = str_split($cin);
        $checksum = 0;

        for ($i = 0; $i < 7; $i++) {
            $checksum += (int)$digits[$i] * ($i + 1);
        }

        $calculatedCheckDigit = $checksum % 10;
        $actualCheckDigit = (int)$digits[7];

        if ($calculatedCheckDigit !== $actualCheckDigit) {
            $this->context->buildViolation('Le numéro CIN n\'est pas valide.')
                ->setParameter('{{ value }}', $cin)
                ->addViolation();
        }
    }
}
