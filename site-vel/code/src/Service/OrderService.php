<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Historique;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function Symfony\Component\Clock\now;

class OrderService
{
    public const array AVAILABLE_PAYMENT_METHODS = ['paypal', 'card', 'bank_transfer'];

    public function __construct(
        private readonly CartService $cartService,
        private readonly WorkflowService $workflowService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function completeOrder(UserInterface $user, Order $order)
    {
        $this->cartService->calculeTotal($order->getCart());
        $this->workflowService->applyTransition($order, Order::COMPLETED);
        $this->entityManager->persist($order);
        $history = new Historique(
            user: $user,
            action: 'complete',
            etat: Order::COMPLETED,
            comment: null,
            detailAction: "complétion du panier avec un total de {$order->getCartTotal()}"
        );

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    public function createOrder(UserInterface $user, ?Book $book = null, ?int $quantity = null): Order
    {
        $order = new Order();
        $order->setCart(
            $this->cartService->createCart(user: $user, book: $book, quantity: $quantity)
        );
        $order->setStatus(Order::INITIAL);
        $this->entityManager->persist($order);

        $history = new Historique(
            user: $user,
            action: 'create',
            etat: Order::INITIAL,
            comment: null,
            detailAction: "crétation de la commande pour le panier {$order->getCart()->getId()}"
        );

        $this->entityManager->persist($history);

        $this->entityManager->flush();

        return $order;
    }

    public function configurePayment(Order $order, array $payload): void
    {
        $paymentMethod = $payload['mode_paiement'];
        if (!in_array($paymentMethod, self::AVAILABLE_PAYMENT_METHODS)) {
            throw new \InvalidArgumentException('Invalid payment method');
        }
        $order->setPaymentMethod($paymentMethod);
        $order->setDatePayment(now());
        $this->workflowService->applyTransition($order, Order::PAYMENT_PENDING);
    }

    public function handlePayment(UserInterface $user, Order $order, string $paymentSystemResponse): void
    {
        $transition = match ($paymentSystemResponse) {
            'success' => Order::PAYMENT_ACCEPTED,
            'refused' => Order::PAYMENT_REFUSED,
            default => throw new \InvalidArgumentException('Invalid payment response'),
        };
        $this->workflowService->applyTransition($order, $transition);

        $this->entityManager->persist($order);

        $history = new Historique(
            user: $user,
            action: 'payment',
            etat: $order->getStatus(),
            comment: null,
            detailAction: "résultat du paiement: $paymentSystemResponse",
        );
        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    public function confirmProvisioning(UserInterface $user, Order $order): void
    {
        $this->workflowService->applyTransition($order, Order::DELIVERY_TO_PREPARE);
        $this->entityManager->persist($order);

        $history = new Historique(
            user: $user,
            action: 'provisioning',
            etat: $order->getStatus(),
            comment: null,
            detailAction: 'confirmation de la provision de la commande'
        );
        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    public function prepareOrder(UserInterface $user, Order $order): void
    {
        $this->workflowService->applyTransition($order, Order::READY_TO_SHIP);
        $this->entityManager->persist($order);

        $history = new Historique(
            user: $user,
            action: 'prepare',
            etat: $order->getStatus(),
            comment: null,
            detailAction: 'préparation de la commande'
        );
        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    public function shipOrder(UserInterface $user, Order $order, array $payload): void
    {
        $this->workflowService->applyTransition($order, Order::SHIPPED);
        $this->entityManager->persist($order);

        $history = new Historique(
            user: $user,
            action: 'ship',
            etat: $order->getStatus(),
            comment: null,
            detailAction: 'expédition de la commande'
        );
        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    public function deliverOrder(UserInterface $user, Order $order): void
    {
        $this->workflowService->applyTransition($order, Order::DELIVERED);
        $this->entityManager->persist($order);

        $history = new Historique(
            user: $user,
            action: 'deliver',
            etat: $order->getStatus(),
            comment: null,
            detailAction: 'livraison de la commande'
        );
        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }
}
