<?php

namespace App\Controller;

use App\Entity\Guitar;
use App\Enum\OrderStatus;
use App\Repository\CustomerOrderRepository;
use App\Repository\GuitarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/dashboard', name: 'app_home')]
    public function index(CustomerOrderRepository $orderRepository, GuitarRepository $guitarRepository): Response
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
        ]);
    }

    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('app_home');
    }
}
