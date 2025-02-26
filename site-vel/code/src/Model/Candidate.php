<?php

namespace App\Model;

use App\Entity\Role;
use App\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class Candidate
{
    public const VIEW = 'candidate_read';
    public const CREATE = 'candidate_create';

    #[Groups([Candidate::VIEW, Candidate::CREATE])]
    #[Assert\Valid]
    private User $user;

    #[Groups([Candidate::CREATE])]
    #[OA\Property(description: 'Mot de passe', example: 'Password12c!', nullable: false)]
    private string $password;

    public function __construct()
    {
        $this->user = new User();
        $this->user->setRoles([Role::ROLE_CUSTOMER]);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
