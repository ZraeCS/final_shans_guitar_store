<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use App\Repository\CustomerOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ERD table: orders
 * The class is called CustomerOrder because ORDER is a reserved SQL word;
 * the table keeps its ERD name "orders".
 */
#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'idx_orders_status', columns: ['status'])]
#[ORM\Index(name: 'idx_orders_created_at', columns: ['created_at'])]
class CustomerOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** ERD: users ||--o{ orders "places" (FK user_id) */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $customer = null;

    /** Fixed from the old database: DECIMAL instead of INT, to match unit_price. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $total = '0.00';

    /** 'delivery' or 'pickup' (what the store's checkout sends) */
    #[ORM\Column(length: 20, options: ['default' => 'delivery'])]
    private string $fulfillment = 'delivery';

    #[ORM\Column(length: 20, enumType: PaymentMethod::class)]
    private PaymentMethod $paymentMethod = PaymentMethod::Cod;

    /** GCash reference number typed by the customer at checkout */
    #[ORM\Column(length: 40, nullable: true)]
    private ?string $paymentRef = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paymentVerifiedAt = null;

    /** ERD: users o|--o{ orders "verifies payment" (FK payment_verified_by) */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'payment_verified_by', nullable: true, onDelete: 'SET NULL')]
    private ?User $paymentVerifiedBy = null;

    #[ORM\Column(length: 120)]
    private ?string $fullname = null;

    #[ORM\Column(length: 40)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 120)]
    private ?string $city = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20, enumType: OrderStatus::class, options: ['default' => 'pending'])]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $statusUpdatedAt = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * ERD: orders ||--|{ order_items "contains"
     * Replaces the old `items` JSON text column.
     *
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'customerOrder', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    /** Adds up quantity × unit price of every line. */
    public function calculateTotal(): string
    {
        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += $item->getSubtotal();
        }

        return number_format($sum, 2, '.', '');
    }

    public function getFulfillment(): string
    {
        return $this->fulfillment;
    }

    public function setFulfillment(string $fulfillment): static
    {
        $this->fulfillment = $fulfillment;

        return $this;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentRef(): ?string
    {
        return $this->paymentRef;
    }

    public function setPaymentRef(?string $paymentRef): static
    {
        $this->paymentRef = $paymentRef;

        return $this;
    }

    public function getPaymentVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->paymentVerifiedAt;
    }

    public function setPaymentVerifiedAt(?\DateTimeImmutable $paymentVerifiedAt): static
    {
        $this->paymentVerifiedAt = $paymentVerifiedAt;

        return $this;
    }

    public function getPaymentVerifiedBy(): ?User
    {
        return $this->paymentVerifiedBy;
    }

    public function setPaymentVerifiedBy(?User $paymentVerifiedBy): static
    {
        $this->paymentVerifiedBy = $paymentVerifiedBy;

        return $this;
    }

    public function isGcash(): bool
    {
        return $this->paymentMethod === PaymentMethod::Gcash;
    }

    public function isPaymentVerified(): bool
    {
        return $this->paymentVerifiedAt !== null;
    }

    /** A pending GCash order whose reference number nobody has checked yet. */
    public function needsGcashVerification(): bool
    {
        return $this->isGcash() && !$this->isPaymentVerified() && $this->status === OrderStatus::Pending;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = ($notes === '') ? null : $notes;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->statusUpdatedAt;
    }

    public function setStatusUpdatedAt(?\DateTimeImmutable $statusUpdatedAt): static
    {
        $this->statusUpdatedAt = $statusUpdatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCustomerOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }

    /** Total number of units in the order (2 × FG800 + 1 × Strat = 3). */
    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }
}
