<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '"user"')]
#[UniqueEntity('uuid')]
class User implements UserInterface
{
    /** Serialization Groups */
    public const string READ = 'user:read';
    public const string CREATE = 'user:create';

    #[ORM\Id, ORM\Column(type: 'integer', unique: true, nullable: false), ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[Groups(groups: [User::READ])]
    private int $id;

    #[ORM\Column(type: 'string', length: 40, unique: true, nullable: false)]
    #[Groups(groups: [User::READ, User::CREATE])]
    private ?string $uuid = null;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: false)]
    #[Groups(groups: [User::READ, User::CREATE])]
    #[UniqueEntity('email')]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.", mode: 'html5-allow-no-tld')]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Groups(groups: [User::READ, User::CREATE])]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Groups(groups: [User::READ, User::CREATE])]
    private string $lastName;

    #[ORM\Column(type: 'json')]
    #[Groups(groups: [User::READ, User::CREATE])]
    private array $roles;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private \DateTimeInterface $lastConnectedAt;

    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: Review::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $reviews;

    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserAddress::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $addresses;
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: Historique::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $historiques;

    #[ORM\OneToMany(
        mappedBy: 'client',
        targetEntity: Order::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $orders;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->historiques = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    #[Groups(groups: [User::READ])]
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // Nothing to do
    }

    public function getUserIdentifier(): string
    {
        return $this->uuid;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): User
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function setFirstName(string $firstName): User
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): User
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setRoles(array $roles): User
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function setReviews(Collection $reviews): void
    {
        $this->reviews = $reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setUser($this);
        }

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;

        return $this;
    }

    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): User
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques->add($historique);
            $historique->setUser($this);
        }

        return $this;
    }

    public function setLastConnectedAt(\DateTimeInterface $now): self
    {
        $this->lastConnectedAt = $now;

        return $this;
    }

    public function getLastConnectedAt(): \DateTimeInterface
    {
        return $this->lastConnectedAt;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): void
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setClient($this);
        }
    }

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddresses(Address $address): void
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }
    }
}
