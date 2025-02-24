<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Clock\now;

/** A review of a book. */
#[ORM\Entity]
class Review
{
    /** Common serialization groups */
    public const string READ = 'review:read';
    public const string CREATE = 'review:create';

    #[ORM\Id, ORM\Column, ORM\GeneratedValue('SEQUENCE')]
    #[Groups(groups: [Review::READ])]
    private ?int $id = null;

    /** The rating of this review (between 0 and 5). */
    #[Groups(groups: [Review::READ, Review::CREATE])]
    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 0, max: 5)]
    public int $rating = 0;

    /** The body of the review. */
    #[Groups(groups: [Review::READ, Review::CREATE])]
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    public string $body = '';

    /** The author of the review. */
    #[Groups(groups: [Review::READ, Review::CREATE])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[Assert\NotBlank]
    public User $user;

    /** The date of publication of this review.*/
    #[Groups(groups: [Review::READ])]
    #[ORM\Column]
    public ?\DateTimeImmutable $publicationDate = null;

    /** The book this review is about. */
    #[Groups(groups: [Review::READ, Review::CREATE])]
    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[Assert\NotNull]
    public ?Book $book = null;

    public function __construct(int $rating, string $body)
    {
        $this->publicationDate = now();
        $this->rating = $rating;
        $this->body = $body;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): Review
    {
        $this->book = $book;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Review
    {
        $this->user = $user;

        return $this;
    }
}
