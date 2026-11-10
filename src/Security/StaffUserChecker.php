<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who may log in to the admin panel:
 *   - only ACTIVE accounts (an admin can disable staff accounts)
 *   - only STAFF and ADMIN roles (customers use the store)
 *
 * Both checks run AFTER the password is confirmed, so a stranger can't use
 * these messages to find out which emails exist or which accounts are disabled.
 */
final class StaffUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('This account is disabled. Ask an administrator to enable it.');
        }

        if (!$user->isStaffMember()) {
            throw new CustomUserMessageAccountStatusException('This login is for Shan’s Guitar staff. Customers can sign in on the store website.');
        }
    }
}
