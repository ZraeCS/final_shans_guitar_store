<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Repository\CustomerOrderRepository;
use App\Repository\GuitarRepository;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * My Account (staff AND admins): view your own profile and change your password.
 */
#[Route('/dashboard/account')]
#[IsGranted('ROLE_STAFF')]
final class AccountController extends AbstractController
{
    #[Route(name: 'app_account', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
        GuitarRepository $guitarRepository,
        CustomerOrderRepository $orderRepository,
    ): Response {
        /** @var User $me */
        $me = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $me->setPassword($passwordHasher->hashPassword($me, $form->get('newPassword')->getData()));
            $entityManager->flush();

            // The session remembers a fingerprint of the old password, so after a
            // change Symfony would log the user out. Logging them in again with the
            // new password keeps them signed in (the same $security->login() call
            // the professor's RegistrationController uses).
            $security->login($me, LoginAuthenticator::class, 'main');

            $this->addFlash('success', 'Your password was changed.');

            return $this->redirectToRoute('app_account', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/index.html.twig', [
            'passwordForm' => $form,
            'products_added' => $guitarRepository->count(['createdBy' => $me]),
            'payments_verified' => $orderRepository->count(['paymentVerifiedBy' => $me]),
        ]);
    }
}
