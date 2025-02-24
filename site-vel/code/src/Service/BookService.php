<?php

namespace App\Service;

class BookService
{
    public function createUser(string $email, string $firstName, string $lastName, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);

        return $user;
    }
}
