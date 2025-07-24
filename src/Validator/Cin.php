<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Cin extends Constraint
{
    public string $message = 'Le numéro CIN "{{ value }}" n\'est pas valide. Il doit contenir 8 chiffres.';
}
