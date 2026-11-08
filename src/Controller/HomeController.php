<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/dashboard', name: 'app_home')]
    public function index(): Response
    {
        // ---------------------------------------------------------------
        // SAMPLE DATA so the design can be checked before the database
        // exists. In the database step, each value below is replaced by
        // a repository query (for example $orderRepository->findRecent()).
        // The template does not need to change when that happens.
        // ---------------------------------------------------------------

        $todaySales = 51000;
        $yesterdaySales = 45500;

        $lowStock = [
            ['name' => 'PRS Custom 24 10-Top', 'stock' => 2],
            ['name' => 'Martin D-28', 'stock' => 2],
            ['name' => 'Gibson Les Paul Standard', 'stock' => 3],
        ];

        $recentOrders = [
            [
                'id' => 1024, 'customer' => 'Maria Santos', 'items' => 'Yamaha FG800 × 1',
                'total' => 15500, 'method' => 'gcash', 'ref' => '4829-1067-3345', 'verified' => false,
                'status' => 'pending', 'created_at' => new \DateTimeImmutable('-35 minutes'),
            ],
            [
                'id' => 1023, 'customer' => 'Juan Dela Cruz', 'items' => 'Fender Stratocaster II × 1',
                'total' => 35500, 'method' => 'cod', 'ref' => null, 'verified' => false,
                'status' => 'confirmed', 'created_at' => new \DateTimeImmutable('-3 hours'),
            ],
            [
                'id' => 1022, 'customer' => 'Pedro Reyes', 'items' => 'Epiphone SG Standard × 1',
                'total' => 25000, 'method' => 'pickup_pay', 'ref' => null, 'verified' => false,
                'status' => 'shipped', 'created_at' => new \DateTimeImmutable('-1 day'),
            ],
            [
                'id' => 1021, 'customer' => 'Ana Lopez', 'items' => 'Cordoba C5 Classical × 1',
                'total' => 18500, 'method' => 'gcash', 'ref' => '1234-5678-9012', 'verified' => true,
                'status' => 'delivered', 'created_at' => new \DateTimeImmutable('-2 days'),
            ],
            [
                'id' => 1020, 'customer' => 'Hochimin', 'items' => 'Martin D-28 × 1',
                'total' => 178000, 'method' => 'pickup_pay', 'ref' => null, 'verified' => false,
                'status' => 'cancelled', 'created_at' => new \DateTimeImmutable('-3 days'),
            ],
        ];

        // Percent change vs yesterday (null when there were no sales yesterday)
        $salesChange = $yesterdaySales > 0
            ? (int) round(($todaySales - $yesterdaySales) / $yesterdaySales * 100)
            : null;

        return $this->render('home/index.html.twig', [
            'today_sales' => $todaySales,
            'sales_change' => $salesChange,
            'orders_today' => 6,
            'orders_awaiting' => 2,
            'gcash_to_verify' => 2,
            'low_stock' => $lowStock,
            'recent_orders' => $recentOrders,
        ]);
    }

    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('app_home');
    }
}
