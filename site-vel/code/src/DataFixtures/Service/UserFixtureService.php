<?php

namespace App\DataFixtures\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;

class UserFixtureService
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createCustomer(array $config): User
    {
        $customer = $this->userService->createCustomer(
            firstName: $config['client']['prenom'],
            lastName: $config['client']['nom'],
            email: $config['client']['email'],
            password: "1A{$config['client']['email']}"
        );
        foreach ($config['client']['adresses'] as $addressConfig) {
            $address = new Address();
            $address->setAddress1($addressConfig['rue']);
            $address->setAddress2($addressConfig['complement']);
            $address->setCity($addressConfig['ville']);
            $address->setZipCode($addressConfig['code_postal']);
            $customer->addAddress($address);
            $this->entityManager->persist($address);
        }

        $this->entityManager->persist($customer);

        return $customer;
    }
}
