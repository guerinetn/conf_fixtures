<?php

namespace App\Service;

use App\Entity\Historique;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IdentityService $identityService,
    ) {
    }

    public function createCustomer(string $firstName, string $lastName, string $email, string $password): User
    {
        $customer = new User();
        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setEmail($email);
        $customer->setRoles([Role::ROLE_CUSTOMER]);

        $customer->setUuid($this->identityService->createUser($customer, $password));

        $history = new Historique(
            user: $customer,
            action: 'create',
            detailAction: "création du client {$customer->getEmail()}"
        );
        $this->entityManager->persist($history);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    public function createSeller(string $firstName, string $lastName, string $email, string $password): void
    {
        $manager = new User();
        $manager->setFirstName($firstName);
        $manager->setLastName($lastName);
        $manager->setEmail($email);
        $manager->setRoles([Role::ROLE_SELLER]);

        $manager->setUuid($this->identityService->createUser($manager, $password));

        $history = new Historique(
            user: $manager,
            action: 'create',
            detailAction: "création du vendeur {$manager->getEmail()}"
        );
        $this->entityManager->persist($history);
        $this->entityManager->persist($manager);
        $this->entityManager->flush();
    }

    public function getTechnicalUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['roles' => Role::ROLE_TECHNICAL]);
    }
}
