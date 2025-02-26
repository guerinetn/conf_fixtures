<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Cart;
use App\Entity\CartBooks;
use App\Entity\Historique;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CartService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowService $workflowService,
    ) {
    }

    public function createCart(UserInterface $user, ?Book $book, int $quantity): Cart
    {
        $cart = new Cart();
        $cartBook = new CartBooks();
        if (null === $book) {
            $this->entityManager->persist($cart);
            $history = new Historique(
                user: $user,
                action: 'create',
                etat: Order::INITIAL,
                comment: null,
                detailAction: 'crétation du panier sans livre'
            );
            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return $cart;
        }

        $cartBook->setBook($book);
        $cartBook->setQuantity($quantity);
        $cart->addBook($cartBook);

        $this->entityManager->persist($cart);

        $history = new Historique(
            user: $user,
            action: 'create',
            etat: Order::INITIAL,
            comment: null,
            detailAction: "crétation du panier avec le livre {$book->getTitle()} en quantité {$quantity}"
        );

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return $cart;
    }

    public function addBook(UserInterface $user, Cart $cart, Book $book, int $quantity): void
    {
        foreach ($cart->getCartBooks() as $cartBook) {
            if ($cartBook->getBook() === $book) {
                $cartBook->setQuantity($cartBook->getQuantity() + $quantity);
                $this->entityManager->persist($cartBook);
                $this->entityManager->flush();
                $history = new Historique(
                    user: $user,
                    action: 'modify',
                    etat: Order::INITIAL,
                    comment: null,
                    detailAction: "Ajout de {$quantity} livre {$book->getTitle()} au panier"
                );
                $this->entityManager->persist($history);

                return;
            }
        }
        $cartBook = new CartBooks();
        $cartBook->setBook($book);
        $cartBook->setQuantity($quantity);
        $cart->addBook($cartBook);

        $this->entityManager->persist($cart);

        $history = new Historique(
            user: $user,
            action: 'create',
            etat: Order::INITIAL,
            comment: null,
            detailAction: "crétation du panier avec le livre {$book->getTitle()} en quantité {$quantity}"
        );

        $this->entityManager->persist($history);
    }

    public function calculeTotal(Cart $cart): void
    {
        $total = 0;
        foreach ($cart->getCartBooks() as $cartBook) {
            $total += $cartBook->getBook()->getPrice() * $cartBook->getQuantity();
        }
        $cart->setCartTotal($total);
    }
}
