<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Registration of a new STAFF account (the professor's
 * User_Entity_Login_Register lesson, used inside the admin panel).
 *
 * Differences from the public sign-up in his lesson, on purpose:
 *  - The URL starts with /dashboard, so security.yaml only lets ADMINS in:
 *    only an admin can create another admin.
 *  - The new account gets role = 'admin'. (Customers sign up on the store.)
 *  - His version logs the NEW user in at the end ($security->login()).
 *    Here an admin creates an account for someone else, so the admin stays
 *    logged in and goes back to Settings instead.
 */
class RegistrationController extends AbstractController
{
    #[Route('/dashboard/staff/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password (bcrypt, the same format as the store)
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // staff account: may log in to this admin panel
            $user->setRole(UserRole::Admin);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Staff account created for %s. They can log in with %s now.', $user->getName(), $user->getEmail()));

            return $this->redirectToRoute('app_settings', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
