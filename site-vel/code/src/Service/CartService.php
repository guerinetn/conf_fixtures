<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Cart;
use App\Entity\CartBooks;
use Symfony\Component\Security\Core\User\UserInterface;

class CartService
{
    public function createCart(UserInterface $user,Book $book, int $quantity)
    {
        $cart = new Cart();
        $cartBook = new CartBooks();
        $cartBook->setBook($book);
        $cartBook->setQuantity($quantity);
        $cart->addBook($cartBook);
    }
}
