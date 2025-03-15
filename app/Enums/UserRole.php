<?php

namespace App\Enums;

enum UserRole: int
{
    case USER = 1;
    case ADMIN = 2;

    public function label(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Admin',
        };
    }
}
