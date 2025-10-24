<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unPaid';
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
