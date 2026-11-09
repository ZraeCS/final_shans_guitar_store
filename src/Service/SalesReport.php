<?php

namespace App\Service;

use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use App\Repository\CustomerOrderRepository;
use App\Repository\OrderItemRepository;

/**
 * Builds the numbers for the Sales Reports page.
 *
 * Rule used everywhere (same as the dashboard): CANCELLED orders are not
 * sales. They are counted separately so nothing is hidden.
 */
final class SalesReport
{
    /** The quick ranges shown as buttons on the page. */
    public const RANGES = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'all' => 'All time',
    ];

    public function __construct(
        private readonly CustomerOrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
    ) {
    }

    /**
     * Turns the page's choice (?range=7d or ?from=2026-11-01&to=2026-11-09)
     * into a start (included) and an end (excluded, so "to" includes the whole day).
     *
     * @return array{from: \DateTimeImmutable, to: \DateTimeImmutable, range: string, label: string}
     */
    public function resolveRange(string $range, string $fromInput = '', string $toInput = ''): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        // custom dates win when both are valid
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $fromInput) ?: null;
        $to = \DateTimeImmutable::createFromFormat('!Y-m-d', $toInput) ?: null;
        if ($from && $to) {
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }

            return [
                'from' => $from,
                'to' => $to->modify('+1 day'),
                'range' => 'custom',
                'label' => $from->format('M j, Y').' – '.$to->format('M j, Y'),
            ];
        }

        $range = array_key_exists($range, self::RANGES) ? $range : '30d';
        $from = match ($range) {
            'today' => $today,
            '7d' => $today->modify('-6 days'),
            '30d' => $today->modify('-29 days'),
            'month' => $today->modify('first day of this month'),
            'all' => ($this->orders->findFirstOrderDate() ?? $today)->setTime(0, 0),
        };

        return ['from' => $from, 'to' => $tomorrow, 'range' => $range, 'label' => self::RANGES[$range]];
    }

    /**
     * Everything the report page shows, for orders placed in [$from, $to).
     */
    public function build(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->orders->findForReport($from, $to);

        // Group by day for short ranges, by month for long ones (keeps the chart readable)
        $byMonth = $from->diff($to)->days > 31;
        $bucketFormat = $byMonth ? 'Y-m' : 'Y-m-d';

        // Every day (or month) of the range, also the ones without sales
        $buckets = [];
        $cursor = $byMonth ? $from->modify('first day of this month') : $from;
        while ($cursor < $to) {
            $buckets[$cursor->format($bucketFormat)] = [
                'label' => $cursor->format($byMonth ? 'M Y' : 'D, M j'),
                'revenue' => 0.0,
                'orders' => 0,
            ];
            $cursor = $cursor->modify($byMonth ? '+1 month' : '+1 day');
        }

        $methods = [];
        foreach (PaymentMethod::cases() as $method) {
            $methods[$method->value] = ['label' => $method->label(), 'orders' => 0, 'revenue' => 0.0];
        }
        $statuses = array_fill_keys(array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases()), 0);

        $revenue = 0.0;
        $sales = 0;
        foreach ($rows as $row) {
            $status = $this->value($row['status']);
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
            if ($status === OrderStatus::Cancelled->value) {
                continue;
            }

            $total = (float) $row['total'];
            $revenue += $total;
            ++$sales;

            $key = $row['createdAt']->format($bucketFormat);
            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += $total;
                ++$buckets[$key]['orders'];
            }

            $method = $this->value($row['paymentMethod']);
            if (isset($methods[$method])) {
                $methods[$method]['revenue'] += $total;
                ++$methods[$method]['orders'];
            }
        }

        $products = $this->orderItems->findTopProducts($from, $to, 1000);
        $units = array_sum(array_map(fn (array $p) => (int) $p['units'], $products));

        return [
            'revenue' => $revenue,
            'sales' => $sales,
            'average' => $sales > 0 ? $revenue / $sales : 0.0,
            'units' => $units,
            'cancelled' => $statuses[OrderStatus::Cancelled->value],
            'by_period' => array_values($buckets),
            'period_unit' => $byMonth ? 'month' : 'day',
            'best_period' => max([0.0, ...array_column($buckets, 'revenue')]),
            'by_method' => array_values($methods),
            // [['status' => OrderStatus, 'count' => int], ...] so the template can use the status badge
            'by_status' => array_map(fn (OrderStatus $s) => ['status' => $s, 'count' => $statuses[$s->value]], OrderStatus::cases()),
            'top_products' => array_slice($products, 0, 5),
        ];
    }

    /** Enum fields can come back as enum objects or plain strings. */
    private function value(mixed $v): string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }
}
