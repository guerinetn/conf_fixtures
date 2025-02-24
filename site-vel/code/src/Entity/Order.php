<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Order
{
    public const READ = 'order:read';
    public const CREATE = 'order:create';

    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $status;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    private User $client;

    private Cart $cart;

    private Address $deliveryAddress;

    private Address $billingAddress;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClient(): User
    {
        return $this->client;
    }

    public function setClient(User $client): void
    {
        $this->client = $client;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function getDeliveryAddress(): Address
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(Address $deliveryAddress): void
    {
        $this->deliveryAddress = $deliveryAddress;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }


}
