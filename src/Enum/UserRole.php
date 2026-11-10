<?php

namespace App\Enum;

/**
 * The `role` column of the users table (customers, staff and admins share it).
 *
 *   user  -> a customer of the store (cannot open the admin panel)
 *   staff -> works at the shop: products (own), orders, GCash, customers
 *   admin -> everything staff can do + dashboard, reports and user management
 *
 * User::getRoles() turns these into Symfony roles (ROLE_USER, ROLE_STAFF,
 * ROLE_ADMIN); security.yaml says ROLE_ADMIN includes ROLE_STAFF.
 */
enum UserRole: string
{
    case User = 'user';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Customer',
            self::Staff => 'Staff',
            self::Admin => 'Admin',
        };
    }
}
