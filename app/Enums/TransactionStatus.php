<?php 
namespace App\Enums;

enum TransactionStatus: string{
    case Unpaid = "unpaid";
    case Paid = "paid";
    case Failed = 'failed';

}