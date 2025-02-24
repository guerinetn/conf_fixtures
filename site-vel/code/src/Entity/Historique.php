<?php

namespace App\Entity;

use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Clock\now;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
#[ORM\Index(columns: ['date'], name: 'dateHistoriqueIdx')]
#[ORM\Index(columns: ['action'], name: 'actionHistoriqueIdx')]
#[ORM\Index(columns: ['etat', 'action', 'date'], name: 'etatActionDateHistoriqueIdx')]
class Historique
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[Groups(['log_read'])]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['log_create', 'log_read'])]
    private ?string $etat = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Groups(['log_create', 'log_read'])]
    private string $action;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['log_create', 'log_read'])]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['log_read'])]
    private \DateTimeInterface $date;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'historiques')]
    #[Groups(['log_create', 'log_read'])]
    #[Assert\Valid]
    private ?User $user;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['log_create', 'log_read'])]
    private ?string $detailAction = null;

    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(
        UserInterface $user,
        string $action,
        ?string $etat = null,
        ?string $comment = null,
        ?string $detailAction = null,
    ) {
        $this->user = $user;
        $this->etat = $etat;
        $this->action = $action;
        $this->commentaire = $comment;
        $this->date = now();
        $this->detailAction = $detailAction;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDetailAction(): ?string
    {
        return $this->detailAction;
    }

    public function setDetailAction(?string $detailAction): self
    {
        $this->detailAction = $detailAction;

        return $this;
    }
}
