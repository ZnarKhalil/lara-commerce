<?php

namespace App\Enums;

enum PaymentMethod: int
{
    case CREDIT_CARD = 1;
    case PAYPAL = 2;
    case BANK_TRANSFER = 3;
    case CASH_ON_DELIVERY = 4;
}
