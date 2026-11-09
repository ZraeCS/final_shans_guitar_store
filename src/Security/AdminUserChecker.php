<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The admin panel is for staff only.
 *
 * Customers and admins share the `users` table, so a customer's email and
 * password are valid. This check runs AFTER the password is confirmed and
 * refuses everyone whose role isn't 'admin', with a clear message.
 * (Checking after the password means a stranger can't use this message to
 * find out which emails belong to customers.)
 */
final class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isAdmin()) {
            throw new CustomUserMessageAccountStatusException('This login is for Shan’s Guitar staff. Customers can sign in on the store website.');
        }
    }
}
