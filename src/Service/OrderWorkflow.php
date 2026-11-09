<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * All the rules for moving an order through its life cycle, in one place.
 * The Orders page, the GCash page and the dashboard buttons all use this,
 * so they always follow the same rules (the same ones the store's old admin had):
 *
 *   pending ──> confirmed ──> shipped ──> delivered
 *      │            │
 *      └────────────┴──> cancelled   (items go back into stock, once)
 *
 *   A GCash order can only become "confirmed" through verifyGcash().
 */
final class OrderWorkflow
{
    /** Which status may follow which. */
    private const NEXT = [
        'pending' => [OrderStatus::Confirmed, OrderStatus::Cancelled],
        'confirmed' => [OrderStatus::Shipped, OrderStatus::Cancelled],
        'shipped' => [OrderStatus::Delivered],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /** @return OrderStatus[] the statuses this order may move to right now */
    public function nextStatuses(CustomerOrder $order): array
    {
        $next = self::NEXT[$order->getStatus()->value];

        // An unverified GCash order can't be confirmed by hand: use verifyGcash()
        if ($order->needsGcashVerification()) {
            $next = array_values(array_filter($next, fn (OrderStatus $s) => $s !== OrderStatus::Confirmed));
        }

        return $next;
    }

    public function canMoveTo(CustomerOrder $order, OrderStatus $status): bool
    {
        return in_array($status, $this->nextStatuses($order), true);
    }

    /**
     * Moves the order to a new status, or throws \DomainException with a
     * message the admin can read.
     */
    public function changeStatus(CustomerOrder $order, OrderStatus $status): void
    {
        if ($status === OrderStatus::Confirmed && $order->needsGcashVerification()) {
            throw new \DomainException(sprintf('Order #%d is paid by GCash. Verify the payment first.', $order->getId()));
        }

        if (!$this->canMoveTo($order, $status)) {
            throw new \DomainException(sprintf(
                'Order #%d is %s and can’t be marked %s.',
                $order->getId(), strtolower($order->getStatus()->label()), strtolower($status->label())
            ));
        }

        // Cancelling puts every unit back on the shelf. This only runs on the
        // way INTO "cancelled", and "cancelled" is final, so stock is never
        // returned twice.
        if ($status === OrderStatus::Cancelled) {
            foreach ($order->getItems() as $item) {
                $guitar = $item->getGuitar();
                $guitar->setStock($guitar->getStock() + $item->getQuantity());
            }
        }

        $order->setStatus($status);
        $order->setStatusUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * The admin checked the GCash app and saw the money arrive:
     * stamp who verified it and when, and confirm the order.
     */
    public function verifyGcash(CustomerOrder $order, User $admin): void
    {
        if (!$order->needsGcashVerification()) {
            throw new \DomainException(sprintf('Order #%d has no GCash payment waiting for a check.', $order->getId()));
        }

        $now = new \DateTimeImmutable();
        $order->setPaymentVerifiedAt($now);
        $order->setPaymentVerifiedBy($admin);
        $order->setStatus(OrderStatus::Confirmed);
        $order->setStatusUpdatedAt($now);
        $this->entityManager->flush();
    }
}
