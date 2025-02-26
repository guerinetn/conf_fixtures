<?php

namespace App\Entity;

enum Role: string
{
    case ROLE_ANONYMOUS = 'anonyme';
    case ROLE_CUSTOMER = 'customer';
    case ROLE_SELLER = 'seller';
    case ROLE_TECHNICAL = 'technique';
}
