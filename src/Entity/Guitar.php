<?php

namespace App\Entity;

use App\Enum\Availability;
use App\Repository\GuitarRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ERD table: guitars
 * Guitars AND accessories (strings, gig bags...) — the category says which.
 */
#[ORM\Entity(repositoryClass: GuitarRepository::class)]
#[ORM\Table(name: 'guitars')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['itemCode'], message: 'Another product already uses this item code.')]
class Guitar
{
    /** "Low stock" means this many units or fewer (same rule as the store's old admin). */
    public const LOW_STOCK_THRESHOLD = 3;

    /** The categories the store's shop filters use. */
    public const CATEGORIES = ['Electric', 'Acoustic', 'Bass', 'Classical', 'Accessory'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Enter the product name.')]
    #[Assert\Length(max: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Enter the brand.')]
    #[Assert\Length(max: 80)]
    private ?string $brand = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Choose a category.')]
    private ?string $category = null;

    /** Money is a string in PHP so no centavos get lost to float rounding. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Enter a price.')]
    #[Assert\Positive(message: 'The price must be more than ₱0.')]
    private ?string $price = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Stock can’t be negative.')]
    private int $stock = 0;

    /** Path relative to the store website, e.g. images/products/Martin_D28.jpg */
    #[ORM\Column(length: 255, options: ['default' => ''])]
    private string $image = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isBestseller = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isNew = false;

    /** Fixed from the old database: now required AND unique. */
    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Enter an item code, e.g. SG-0017.')]
    #[Assert\Length(max: 20)]
    private ?string $itemCode = null;

    #[ORM\Column(length: 20, enumType: Availability::class)]
    private Availability $availability = Availability::InStore;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Who added this product (FK created_by). Staff may edit or delete only
     * the products they added (see App\Security\Voter\GuitarVoter); admins
     * may change any product. If that account is deleted, the product stays
     * and this becomes NULL.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'created_by', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /** Runs automatically before Doctrine saves a change (replaces MySQL's ON UPDATE). */
    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = max(0, $stock);

        return $this;
    }

    public function isLowStock(): bool
    {
        return $this->stock <= self::LOW_STOCK_THRESHOLD;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isBestseller(): bool
    {
        return $this->isBestseller;
    }

    public function setIsBestseller(bool $isBestseller): static
    {
        $this->isBestseller = $isBestseller;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setIsNew(bool $isNew): static
    {
        $this->isNew = $isNew;

        return $this;
    }

    public function getItemCode(): ?string
    {
        return $this->itemCode;
    }

    public function setItemCode(string $itemCode): static
    {
        $this->itemCode = $itemCode;

        return $this;
    }

    public function getAvailability(): Availability
    {
        return $this->availability;
    }

    public function setAvailability(Availability $availability): static
    {
        $this->availability = $availability;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
