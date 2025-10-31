<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unPaid';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
