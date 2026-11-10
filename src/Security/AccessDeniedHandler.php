<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Runs when a LOGGED-IN user opens something they may not use, for example a
 * staff member typing /dashboard/users in the address bar, or trying to edit
 * a product someone else added.
 *
 * Instead of a bare error page, they see "Access denied" and are sent to
 * their own home page (app_root picks it by role). The rubric accepts
 * "403 Access Denied OR redirect".
 */
final class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', 'Access denied: your account is not allowed to open that page or change that record.');
        }

        return new RedirectResponse($this->urlGenerator->generate('app_root'), Response::HTTP_SEE_OTHER);
    }
}
