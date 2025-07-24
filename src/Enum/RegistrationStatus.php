<?php

namespace App\Enum;

enum RegistrationStatus: string
{
    case PENDING = 'PENDING';
    case REGISTERED = 'REGISTERED';
    case CANCELLED = 'CANCELLED';
    case CONFIRMED = 'CONFIRMED';
}
