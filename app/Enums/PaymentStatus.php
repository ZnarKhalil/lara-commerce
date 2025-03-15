<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case PENDING = 1;
    case PAID = 2;
    case FAILED = 3;
    case REFUNDED = 4;
}
