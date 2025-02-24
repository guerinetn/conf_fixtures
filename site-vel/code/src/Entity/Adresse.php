<?php

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdresseRepository::class)]
#[ORM\Table(name: 'adresse')]
#[OA\Schema]
class Adresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer', unique: true, nullable: false)]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[OA\Property(description: 'adresse', example: 'numéro, nom de la voie', nullable: false)]
    #[Assert\NotBlank(message: 'adresse1 doit être renseigné.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'adresse1 doit comporter au moins {{ limit }} caractère(s)',
        maxMessage: 'adresse1 ne doit pas excéder {{ limit }} caractères'
    )]
    private string $address1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[OA\Property(description: 'complément adresse', example: 'complément adresse', nullable: true)]
    private ?string $adress2 = null;

    #[ORM\Column(type: 'string', length: 5, nullable: false)]
    #[OA\Property(description: 'code postal', example: '75000', nullable: false)]
    #[Assert\Regex(
        pattern: '/^\d{2}[ ]?\d{3}$/',
        message: 'Le code postal doit comporter 5 chiffres.'
    )]
    private string $postalCode;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Groups([
        'demande_create_step_1', 'demande_read', 'demande_list',
        'etablissement_detail', 'etablissement_api_export',
    ])]
    #[OA\Property(description: 'ville', example: 'Paris', nullable: false)]
    #[Assert\NotBlank(message: 'ville doit être renseigné.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'ville doit comporter au moins {{ limit }} caractère(s)',
        maxMessage: 'ville ne doit pas excéder {{ limit }} caractères'
    )]
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
}
