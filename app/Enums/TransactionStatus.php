<?php 
namespace App\Enums;

enum TransactionStatus: string{
    case UNPAID = "unpaid";
    case PAID = "paid";
    case FAILED = 'failed';

}