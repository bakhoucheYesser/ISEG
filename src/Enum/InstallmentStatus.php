<?php

namespace App\Enum;

enum InstallmentStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';
}
