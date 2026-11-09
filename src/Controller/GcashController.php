<?php

namespace App\Controller;

use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GCash Payments page: the queue of GCash reference numbers to check.
 * The Verify / Reject buttons use OrderController's actions.
 */
#[Route('/dashboard/gcash')]
final class GcashController extends AbstractController
{
    #[Route(name: 'app_gcash_index', methods: ['GET'])]
    public function index(CustomerOrderRepository $orderRepository): Response
    {
        return $this->render('gcash/index.html.twig', [
            'to_verify' => $orderRepository->findGcashToVerify(),
            'recently_verified' => $orderRepository->findRecentlyVerifiedGcash(10),
        ]);
    }
}
