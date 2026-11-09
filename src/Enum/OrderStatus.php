<?php

namespace App\Enum;

/**
 * Order life cycle, same values the store already uses:
 * pending -> confirmed -> shipped -> delivered, or cancelled.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
