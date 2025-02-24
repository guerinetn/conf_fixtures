<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FontLib\Table\Type\name;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class Cart
{
    public const READ = 'cart:read';
    public const CREATE = 'cart:create';

    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    #[Groups(groups: [Cart::READ])]
    private ?int $id = null;

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
}
