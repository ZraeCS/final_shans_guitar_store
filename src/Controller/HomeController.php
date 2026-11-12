<?php

namespace App\Controller;

use App\Entity\Guitar;
use App\Enum\OrderStatus;
use App\Enum\UserRole;
use App\Repository\CustomerOrderRepository;
use App\Repository\GuitarRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class HomeController extends AbstractController
{
    /**
     * The ADMIN dashboard. Protected twice: by security.yaml (^/dashboard$)
     * and by this controller-level check.
     */
    #[Route('/dashboard', name: 'app_home')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(CustomerOrderRepository $orderRepository, GuitarRepository $guitarRepository, UserRepository $userRepository): Response
    {
        // "today" = midnight Manila time (the timezone is set in src/Kernel.php)
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');
        $yesterday = $today->modify('-1 day');

        $todaySales = $orderRepository->getSalesBetween($today, $tomorrow);
        $yesterdaySales = $orderRepository->getSalesBetween($yesterday, $today);

        // Percent change vs yesterday (null when there were no sales yesterday)
        $salesChange = $yesterdaySales > 0
            ? (int) round(($todaySales - $yesterdaySales) / $yesterdaySales * 100)
            : null;

        return $this->render('home/index.html.twig', [
            'today_sales' => $todaySales,
            'sales_change' => $salesChange,
            'orders_today' => $orderRepository->countCreatedBetween($today, $tomorrow),
            'orders_awaiting' => $orderRepository->countByStatus(OrderStatus::Pending),
            'gcash_to_verify' => $orderRepository->countGcashToVerify(),
            'low_stock' => $guitarRepository->findLowStock(),
            'low_stock_threshold' => Guitar::LOW_STOCK_THRESHOLD,
            'recent_orders' => $orderRepository->findRecent(6),
            // System overview: total users, total staff, total records
            'totals' => [
                'users' => $userRepository->count([]),
                'customers' => $userRepository->count(['role' => UserRole::User]),
                'staff' => $userRepository->count(['role' => UserRole::Staff]),
                'admins' => $userRepository->count(['role' => UserRole::Admin]),
                'products' => $guitarRepository->count([]),
                'orders' => $orderRepository->count([]),
            ],
        ]);
    }

    /**
     * Home page by role: admins -> dashboard, staff -> Products & Stock.
     * It lives at /panel because, behind the front door, "/" can belong to the
     * store (port 8080); on port 8000 the front door sends "/" here.
     */
    #[Route('/panel', name: 'app_root')]
    public function root(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }
        if ($this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_product_index');
        }

        return $this->redirectToRoute('app_login');
    }
}
