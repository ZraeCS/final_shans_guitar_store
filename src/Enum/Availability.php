<?php

namespace App\Enum;

enum Availability: string
{
    case InStore = 'in-store';
    case Online = 'online';
    case PreOrder = 'pre-order';
    case ComingSoon = 'coming-soon';

    public function label(): string
    {
        return match ($this) {
            self::InStore => 'In store',
            self::Online => 'Online only',
            self::PreOrder => 'Pre-order',
            self::ComingSoon => 'Coming soon',
        };
    }
}
