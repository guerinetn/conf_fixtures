<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['cart_id', 'book_id'])]
class CartBooks
{
    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    #[Groups(groups: [Cart::READ])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'cartBooks')]
    #[Groups([Cart::READ, Cart::CREATE])]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[Groups([Cart::READ, Cart::CREATE])]
    private ?Book $book = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThan(1)]
    #[Groups([Cart::READ, Cart::CREATE])]
    private int $quantity = 1;

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function setBook(Book $book): void
    {
        $this->book = $book;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
