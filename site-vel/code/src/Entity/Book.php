<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** A book. */
#[ORM\Entity]
#[UniqueEntity('isbn')]
class Book
{
    /** Common serialization groups */
    public const string READ = 'book:read';
    public const string CREATE = 'book:create';

    #[ORM\Id(), ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    #[Groups(groups: [Book::READ])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(groups: [Book::READ])]
    public ?string $isbn;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(groups: [Book::READ])]
    public string $title;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Groups(groups: [Book::READ])]
    public int $stock = 0;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(groups: [Book::READ])]
    #[Assert\GreaterThanOrEqual(0)]
    public ?float $price = null;

    #[ORM\Column(type: 'text')]
    #[Groups(groups: [Book::READ])]
    public string $description;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'books')]
    #[Groups(groups: [Book::READ])]
    public Author $author;

    #[ORM\Column]
    #[Groups(groups: [Book::READ])]
    public ?\DateTimeImmutable $publicationDate;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book', cascade: ['persist', 'remove'])]
    #[Groups(groups: [Book::READ])]
    public Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): Book
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Book
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeImmutable $publicationDate): self
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): Book
    {
        $this->stock = $stock;

        return $this;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReviews(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setBook($this);
        }

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }
}
