<?php

namespace App\Security\Voter;

use App\Entity\Guitar;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may change a product (guitar):
 *   - ADMIN: any product
 *   - STAFF: only the products they added themselves (guitars.created_by)
 *
 * Used in controllers:  $this->denyAccessUnlessGranted(GuitarVoter::EDIT, $guitar)
 * and in templates:     {% if is_granted('GUITAR_EDIT', guitar) %}
 *
 * @extends Voter<string, Guitar>
 */
final class GuitarVoter extends Voter
{
    public const EDIT = 'GUITAR_EDIT';
    public const DELETE = 'GUITAR_DELETE';

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Guitar;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Admins may change any record
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Staff: only their own records
        return $this->accessDecisionManager->decide($token, ['ROLE_STAFF'])
            && $subject->getCreatedBy()?->getId() === $user->getId();
    }
}
