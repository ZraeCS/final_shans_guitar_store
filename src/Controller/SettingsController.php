<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The old Settings page was split into "My Account" (profile + password)
 * and "Users" (staff management). This keeps old links working.
 */
final class SettingsController extends AbstractController
{
    #[Route('/dashboard/settings', name: 'app_settings')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_account', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
