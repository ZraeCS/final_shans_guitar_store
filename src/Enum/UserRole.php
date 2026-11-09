<?php

namespace App\Enum;

/**
 * The `role` column of the users table (one table for customers AND staff).
 * Symfony security needs roles like ROLE_ADMIN; User::getRoles() converts them.
 */
enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Customer',
            self::Admin => 'Admin',
        };
    }
}
