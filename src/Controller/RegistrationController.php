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
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Registration of a new STAFF or ADMIN account (the professor's
 * User_Entity_Login_Register lesson, used inside the admin panel).
 *
 * Differences from the public sign-up in his lesson, on purpose:
 *  - Only ADMINS may open it (security.yaml ^/dashboard/users + #[IsGranted]).
 *  - The admin chooses the role: Staff or Admin. (Customers sign up on the store.)
 *  - His version logs the NEW user in at the end ($security->login()).
 *    Here an admin creates an account for someone else, so the admin stays
 *    logged in and goes back to the Users page instead.
 */
#[IsGranted('ROLE_ADMIN')]
class RegistrationController extends AbstractController
{
    #[Route('/dashboard/users/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = (new User())->setRole(UserRole::Staff); // "Staff" is preselected
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password (bcrypt, the same format as the store)
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('%s account created for %s. They can log in with %s now.', $user->getRole()->label(), $user->getName(), $user->getEmail()));

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
