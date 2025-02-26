<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class UserAddress
{
    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    #[Groups(groups: [User::READ])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Groups(groups: [User::READ])]
    private string $label;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    private Address $address;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'addresses')]
    private User $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
