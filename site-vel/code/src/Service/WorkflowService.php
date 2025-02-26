<?php

namespace App\Service;

use App\Entity\Historique;
use App\Entity\Order;
use App\Exception\VelException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowService
{
    public function __construct(
        private readonly WorkflowInterface $velOrderStateMachine,
        private readonly EntityManager $entityManager,
    ) {
    }

    public function applyTransition(Order $order, string $transition): void
    {
        $initialState = $order->getStatus();
        try {
            $this->velOrderStateMachine->apply($order, $transition);
        } catch (\Exception $exception) {
            $message = "Impossible d\'appliquer la transition $transition sur la commande";
            $context = [
                'order' => $order,
                'message' => $message,
            ];
            throw new VelException(context: $context, code: Response::HTTP_INTERNAL_SERVER_ERROR, previous: $exception);
        }
        $history = new Historique(
            user: $order->getClient(),
            action: 'transition',
            etat: $order->getStatus(),
            comment: null,
            detailAction: "passage de l'état $initialState à l'état {$order->getStatus()}",
        );
        $this->entityManager->persist($history);
    }

    public function canAction(Order $order, string $transition): bool
    {
        $enabledTransitions = $this->velOrderStateMachine->getEnabledTransitions($order);
        foreach ($enabledTransitions as $enabledTransition) {
            if ($enabledTransition->getName() === $transition) {
                return true;
            }
        }

        return false;
    }
}
