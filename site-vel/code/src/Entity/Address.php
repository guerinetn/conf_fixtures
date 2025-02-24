<?php

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'adresse')]
#[ORM\Entity]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer', unique: true, nullable: false)]
    #[Groups(groups: [User::READ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class,inversedBy: 'addresses')]
    #[Groups(groups: [User::READ])]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[OA\Property(description: 'adresse', example: 'numéro, nom de la voie', nullable: false)]
    #[Assert\NotBlank(message: 'adresse1 doit être renseigné.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'adresse1 doit comporter au moins {{ limit }} caractère(s)',
        maxMessage: 'adresse1 ne doit pas excéder {{ limit }} caractères'
    )]
    #[Groups(groups: [User::READ])]
    private string $address1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[OA\Property(description: 'complément adresse', example: 'complément adresse', nullable: true)]
    #[Groups(groups: [User::READ])]
    private ?string $adress2 = null;

    #[ORM\Column(type: 'string', length: 5, nullable: false)]
    #[OA\Property(description: 'code postal', example: '75000', nullable: false)]
    #[Assert\Regex(
        pattern: '/^\d{2}[ ]?\d{3}$/',
        message: 'Le code postal doit comporter 5 chiffres.'
    )]
    #[Groups(groups: [User::READ])]
    private string $postalCode;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[OA\Property(description: 'ville', example: 'Paris', nullable: false)]
    #[Assert\NotBlank(message: 'ville doit être renseigné.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'ville doit comporter au moins {{ limit }} caractère(s)',
        maxMessage: 'ville ne doit pas excéder {{ limit }} caractères'
    )]
    #[Groups(groups: [User::READ])]
    private string $city;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAddress1(): string
    {
        return $this->address1;
    }

    public function setAddress1(string $address1): self
    {
        $this->address1 = $address1;

        return $this;
    }

    public function getAdress2(): ?string
    {
        return $this->adress2;
    }

    public function setAdress2(?string $adress2): self
    {
        $this->adress2 = $adress2;

        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
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
