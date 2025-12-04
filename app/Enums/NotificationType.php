<?php

namespace App\Enums;

enum NotificationType: string
{
    case PAYMENT = 'payment';
    case REGISTRATION = 'registration';
}