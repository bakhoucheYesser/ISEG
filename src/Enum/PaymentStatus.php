<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case NOT_PAID = 'NOT_PAID';
    case PARTIAL = 'PARTIAL';
    case FULLY_PAID = 'FULLY_PAID';
    case REFUNDED = 'REFUNDED';
    case CANCELLED = 'CANCELLED';
}
