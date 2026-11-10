<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Enum\UserStatus;
use App\Form\AdminResetPasswordType;
use App\Form\UserEditType;
use App\Repository\CustomerOrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Users page: manage every account (ADMIN ONLY).
 * View all accounts, edit name / email / role / status, reset a password,
 * disable or enable, and delete with confirmation.
 * Creating accounts is RegistrationController (the registration lesson).
 *
 * Safety rules, so the shop can never lock itself out:
 *  - you can't delete, demote or disable YOURSELF
 *  - the LAST active admin can't be deleted, demoted or disabled
 *  - a customer who has orders can't be deleted (the orders need them): disable instead
 */
#[Route('/dashboard/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $request->query->getString('q');
        $role = UserRole::tryFrom($request->query->getString('role'));
        $status = UserStatus::tryFrom($request->query->getString('status'));

        return $this->render('user/index.html.twig', [
            'users' => $this->userRepository->searchAccounts($query, $role, $status),
            'query' => $query,
            'current_role' => $role,
            'current_status' => $status,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
            'counts' => [
                'all' => $this->userRepository->count([]),
                'admin' => $this->userRepository->count(['role' => UserRole::Admin]),
                'staff' => $this->userRepository->count(['role' => UserRole::Staff]),
                'user' => $this->userRepository->count(['role' => UserRole::User]),
            ],
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $account): Response
    {
        $isSelf = $this->isSelf($account);
        $wasActiveAdmin = $account->isAdmin() && $account->isActive();

        $form = $this->createForm(UserEditType::class, $account, ['is_self' => $isSelf]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stillActiveAdmin = $account->isAdmin() && $account->isActive();
            if ($wasActiveAdmin && !$stillActiveAdmin && $this->userRepository->countActiveAdmins() <= 1) {
                // countActiveAdmins() still reads the database, where this admin is the only one
                $this->entityManager->refresh($account);
                $this->addFlash('error', sprintf('%s is the last active admin and must stay an active admin.', $account->getName()));

                return $this->redirectToRoute('app_user_edit', ['id' => $account->getId()], Response::HTTP_SEE_OTHER);
            }

            $this->entityManager->flush();
            $this->addFlash('success', sprintf('%s was updated.', $account->getName()));

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'account' => $account,
            'form' => $form,
            'password_form' => $this->createForm(AdminResetPasswordType::class, null, [
                'action' => $this->generateUrl('app_user_password', ['id' => $account->getId()]),
            ]),
            'is_self' => $isSelf,
        ]);
    }

    /** Reset password: the admin sets a new password for this account. */
    #[Route('/{id}/password', name: 'app_user_password', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resetPassword(Request $request, User $account, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(AdminResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $account->setPassword($passwordHasher->hashPassword($account, $form->get('newPassword')->getData()));
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('The password of %s was reset. Give them the new password privately.', $account->getName()));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', 'Password not changed: '.$error->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_edit', ['id' => $account->getId()], Response::HTTP_SEE_OTHER);
    }

    /** Disable / enable an account (the "archive" option for staff). */
    #[Route('/{id}/status', name: 'app_user_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, User $account): Response
    {
        if (!$this->isCsrfTokenValid('user'.$account->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'That button expired. Please try again.');
        } elseif ($this->isSelf($account)) {
            $this->addFlash('error', 'You can’t disable your own account.');
        } elseif ($account->isActive() && $account->isAdmin() && $this->userRepository->countActiveAdmins() <= 1) {
            $this->addFlash('error', sprintf('%s is the last active admin and can’t be disabled.', $account->getName()));
        } else {
            $account->setStatus($account->isActive() ? UserStatus::Disabled : UserStatus::Active);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('%s is now %s.', $account->getName(), strtolower($account->getStatus()->label())));
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $account, CustomerOrderRepository $orderRepository): Response
    {
        $name = $account->getName();

        if (!$this->isCsrfTokenValid('delete-user'.$account->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'That button expired. Please try again.');
        } elseif ($this->isSelf($account)) {
            $this->addFlash('error', 'You can’t delete your own account.');
        } elseif ($account->isAdmin() && $account->isActive() && $this->userRepository->countActiveAdmins() <= 1) {
            $this->addFlash('error', sprintf('%s is the last active admin and can’t be deleted.', $name));
        } elseif (($orders = $orderRepository->count(['customer' => $account])) > 0) {
            $this->addFlash('error', sprintf('%s can’t be deleted: they have %d order%s that must stay on record. Disable the account instead.', $name, $orders, $orders === 1 ? '' : 's'));
        } else {
            // Products they added and payments they verified stay (those links become empty)
            $this->entityManager->remove($account);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('The account of %s was deleted.', $name));
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    private function isSelf(User $account): bool
    {
        $me = $this->getUser();

        return $me instanceof User && $me->getId() === $account->getId();
    }
}
