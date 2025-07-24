<?php

namespace App\Enum;

enum PaymentType: string
{
    case REGISTRATION = 'REGISTRATION';
    case FORMATION = 'FORMATION';
    case PARTIAL = 'PARTIAL';
    case FULL = 'FULL';
}
