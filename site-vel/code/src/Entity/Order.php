<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Order
{
    public const READ = 'order:read';
    public const CREATE = 'order:create';

    public const string INITIAL = 'intial';
    public const string OUTDATED = 'outdated';
    public const string COMPLETED = 'completed';
    public const string PAYMENT_REFUSED = 'payment_refused';
    public const string PAYMENT_ACCEPTED = 'payment_accepted';
    public const string PAYMENT_PENDING = 'payment_pending';
    public const string SUPPLYING_IN_PROGRESS = 'supplying_in_progress';
    public const string DELIVERY_TO_PREPARE = 'delivery_to_prepare';
    public const string READY_TO_SHIP = 'ready_to_ship';
    public const string SHIPPED = 'shipped';
    public const string DELIVERED = 'delivered';
    public const string FINISHED = 'finished';
    public const string IN_LITIGATION = 'in_litigation';
    public const string LITIGATION_HANDLED = 'litigation_handled';

    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $status;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    private User $client;

    #[ORM\OneToOne(targetEntity: Cart::class)]
    private Cart $cart;

    #[ORM\OneToOne(targetEntity: Address::class)]
    private Address $deliveryAddress;

    #[ORM\OneToOne(targetEntity: Address::class)]
    private Address $billingAddress;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $paymentMethod;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $datePayment;

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

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getDatePayment(): \DateTimeInterface
    {
        return $this->datePayment;
    }

    public function setDatePayment(\DateTimeInterface $datePayment): void
    {
        $this->datePayment = $datePayment;
    }
}
