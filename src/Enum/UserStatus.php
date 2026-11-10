<?php

namespace App\Enum;

/**
 * Account status: a disabled account keeps its history (orders, products,
 * verifications) but can no longer log in. This is the "disable / archive"
 * option for staff accounts.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
