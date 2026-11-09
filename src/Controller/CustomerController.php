<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerOrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Customers page: everyone who signed up on the store (role = 'user'),
 * with how much they ordered. Read-only: customers manage their own
 * profile on the store.
 */
#[Route('/dashboard/customers')]
final class CustomerController extends AbstractController
{
    #[Route(name: 'app_customer_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $query = $request->query->getString('q');

        return $this->render('customer/index.html.twig', [
            'rows' => $userRepository->findCustomersWithStats($query),
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $customer, CustomerOrderRepository $orderRepository): Response
    {
        // staff accounts are listed in Settings, not here
        if ($customer->isAdmin()) {
            throw $this->createNotFoundException();
        }

        $orders = $orderRepository->findBy(['customer' => $customer], ['createdAt' => 'DESC']);

        $spent = 0.0;
        $active = 0;
        foreach ($orders as $order) {
            if ($order->getStatus()->value !== 'cancelled') {
                $spent += (float) $order->getTotal();
                ++$active;
            }
        }

        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
            'orders' => $orders,
            'spent' => $spent,
            'active_orders' => $active,
        ]);
    }
}
