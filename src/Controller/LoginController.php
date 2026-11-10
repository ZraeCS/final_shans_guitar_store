<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Already logged in (staff or admin)? Go to your home page.
        if ($this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_root');
        }

        return $this->render('security/login.html.twig', [
            // the error from the last attempt (wrong password, not staff...)
            'error' => $authenticationUtils->getLastAuthenticationError(),
            // the email typed last time, so it doesn't have to be retyped
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
