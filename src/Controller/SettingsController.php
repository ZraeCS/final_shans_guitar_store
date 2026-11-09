<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\ChangePasswordFormType;
use App\Repository\UserRepository;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Settings page: change your own password + the list of staff accounts
 * (with the "Add staff account" button -> RegistrationController).
 */
#[Route('/dashboard/settings')]
final class SettingsController extends AbstractController
{
    #[Route(name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        /** @var User $admin */
        $admin = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $admin->setPassword($passwordHasher->hashPassword($admin, $form->get('newPassword')->getData()));
            $entityManager->flush();

            // The session remembers a fingerprint of the old password, so after a
            // change Symfony would log the admin out. Logging them in again with the
            // new password keeps them signed in (the same $security->login() call
            // the professor's RegistrationController uses).
            $security->login($admin, LoginAuthenticator::class, 'main');

            $this->addFlash('success', 'Your password was changed.');

            return $this->redirectToRoute('app_settings', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/index.html.twig', [
            'passwordForm' => $form,
            'staff' => $userRepository->findBy(['role' => UserRole::Admin], ['name' => 'ASC']),
        ]);
    }
}
