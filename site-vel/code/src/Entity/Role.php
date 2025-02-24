<?php

namespace App\Entity;

enum Role: string
{
    case ROLE_ANONYMOUS = 'anonyme';
    case ROLE_CLIENT = 'client';
    case ROLE_VENDEUR = 'vendeur';
    case ROLE_CRON = 'technique';
}
