<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ERD table: order_items
 * One row per guitar in an order. unit_price is a snapshot of the price at the
 * moment of purchase, so old orders never change when a price changes later.
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private ?CustomerOrder $customerOrder = null;

    /** RESTRICT: a guitar that appears in an order can't be deleted (hide it instead). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'guitar_id', nullable: false, onDelete: 'RESTRICT')]
    private ?Guitar $guitar = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(?CustomerOrder $customerOrder): static
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }

    public function getGuitar(): ?Guitar
    {
        return $this->guitar;
    }

    public function setGuitar(Guitar $guitar): static
    {
        $this->guitar = $guitar;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getSubtotal(): float
    {
        return $this->quantity * (float) $this->unitPrice;
    }
}
