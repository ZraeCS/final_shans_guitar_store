<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ERD table: users
 * Customers and staff share this table. The `role` column ('user' or 'admin')
 * decides who may open the admin panel.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Enter a name.')]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 190, unique: true)]
    #[Assert\NotBlank(message: 'Enter an email address.')]
    #[Assert\Email(message: 'That doesn’t look like an email address.')]
    #[Assert\Length(max: 190)]
    private ?string $email = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\Regex(pattern: '/^[0-9+()\-\s]{7,20}$/', message: 'Enter a valid phone number.')]
    private ?string $phone = null;

    /** The hashed password (bcrypt, the same format the store's password_hash() makes). */
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 10, enumType: UserRole::class)]
    private UserRole $role = UserRole::User;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = ($phone === '') ? null : $phone;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
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

    /** First letter of the name, used for the round avatar in the admin header. */
    public function getInitial(): string
    {
        return mb_strtoupper(mb_substr((string) $this->name, 0, 1));
    }

    // ------------------------------------------------------------------
    // Symfony security (used by the admin login in Milestone 3)
    // ------------------------------------------------------------------

    /** The value people type to log in. */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Converts the ERD's single `role` column into Symfony roles:
     *   'admin' -> ROLE_ADMIN (+ ROLE_USER)
     *   'user'  -> ROLE_USER
     */
    public function getRoles(): array
    {
        return $this->isAdmin() ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
    }

    /**
     * Keeps the real password hash out of the session file
     * (same technique as the professor's User entity).
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', (string) $this->password);

        return $data;
    }
}
