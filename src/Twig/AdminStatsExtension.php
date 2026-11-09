<?php

namespace App\Twig;

use App\Enum\OrderStatus;
use App\Repository\CustomerOrderRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Gives every template two small functions for the sidebar badges:
 *
 *     {{ pending_orders_count() }}
 *     {{ gcash_to_verify_count() }}
 *
 * The sidebar appears on every admin page, so the numbers come from here
 * instead of being passed by each controller.
 */
final class AdminStatsExtension extends AbstractExtension
{
    public function __construct(private readonly CustomerOrderRepository $orders)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_orders_count', fn (): int => $this->orders->countByStatus(OrderStatus::Pending)),
            new TwigFunction('gcash_to_verify_count', fn (): int => $this->orders->countGcashToVerify()),
        ];
    }
}
