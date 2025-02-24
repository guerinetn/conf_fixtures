<?php

namespace App\Entity;

class Log
{
    private int $id;

    private User $user;

    private \DateTimeInterface $date;

    private string $action;

    private ?string $complement = null;

    public function __construct(int $id, User $user)
    {
    }
}
