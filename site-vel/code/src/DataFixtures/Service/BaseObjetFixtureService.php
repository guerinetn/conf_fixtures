<?php

namespace App\DataFixtures\Service;

use App\DataFixtures\AppFixtures;
use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Persistence\ObjectManager;

class BaseObjetFixtureService
{
    public function createBook(AppFixtures $fixtures, ObjectManager $manager, array $config): void
    {
        $book = new Book();
        $book->setTitle($config['titre']);
        $book->setAuthor($fixtures->getReference(name: $config['auteur'], class: Author::class));
        $book->setPrice($config['prix']);
        $book->setIsbn($config['isbn']);
        $book->setDescription($config['description']);

        $manager->persist($book);
        $fixtures->addReference($config['reference'], $book);
    }

    public function createAuthor(AppFixtures $fixtures, ObjectManager $manager, array $config): void
    {
        $author = new Author();
        $author->setFirstName($config['prenom']);
        $author->setLastName($config['nom']);

        $manager->persist($author);
        $fixtures->addReference($config['reference'], $author);
    }
}
