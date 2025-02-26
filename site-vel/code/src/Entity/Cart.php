<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class Cart
{
    public const READ = 'cart:read';
    public const CREATE = 'cart:create';

    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    #[Groups(groups: [Cart::READ])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(groups: [Cart::READ])]
    private ?int $cartTotal = null;

    #[ORM\OneToMany(
        mappedBy: 'cart',
        targetEntity: CartBooks::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups(groups: [Cart::READ, Cart::CREATE])]
    private ArrayCollection $cartBooks;

    public function __construct()
    {
        $this->cartBooks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCartBooks(): ArrayCollection
    {
        return $this->cartBooks;
    }

    public function addBook(CartBooks $book): self
    {
        if (!$this->cartBooks->contains($book)) {
            $this->cartBooks->add($book);
        }

        return $this;
    }

    public function removeBook(CartBooks $book): self
    {
        if ($this->cartBooks->contains($book)) {
            $this->cartBooks->removeElement($book);
        }

        return $this;
    }

    public function getCartTotal(): int
    {
        if (null !== $this->cartTotal) {
            return $this->cartTotal;
        }
        $cartTotal = 0;
        foreach ($this->cartBooks as $cartBook) {
            $cartTotal += $cartBook->getBook()->getPrice() * $cartBook->getQuantity();
        }

        return $cartTotal;
    }

    public function setCartTotal(?int $cartTotal): void
    {
        $this->cartTotal = $cartTotal;
    }
}
