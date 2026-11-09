<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case PickupPay = 'pickup_pay';
    case Gcash = 'gcash';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'COD',
            self::PickupPay => 'Pay on pickup',
            self::Gcash => 'GCash',
        };
    }
}
