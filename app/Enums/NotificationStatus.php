<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case PENDING = "pending";
    case APPROVED = "approved";
    case REJECTED = "rejected";
}