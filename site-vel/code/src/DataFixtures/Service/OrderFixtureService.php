<?php

namespace App\DataFixtures\Service;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\Exception\FixtureException;
use App\Entity\Book;
use App\Entity\Order;
use App\Entity\User;
use App\Service\CartService;
use App\Service\OrderService;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class OrderFixtureService
{
    private array $defaultScenar;
    public ConsoleOutput $console;

    public function __construct(
        ParameterBagInterface $parameterBag,
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
    ) {
        $this->defaultScenar = Yaml::parseFile($parameterBag->get('app.fixtures.config_default_scenario'));

        foreach ($this->defaultScenar as $keyEntry => $entries) {
            if (is_array($entries)) {
                foreach ($entries as $key => $entry) {
                    if ('<a_modifier>' === $entry || '<optionnel>' === $entry) {
                        unset($this->defaultScenar[$keyEntry][$key]);
                    }
                }
            } elseif ('<a_modifier>' === $entries || '<optionnel>' === $entries) {
                unset($this->defaultScenar[$keyEntry]);
            }
        }

        $this->console = new ConsoleOutput();
    }

    public function createOrder(ObjectManager $manager, array $config, AppFixtures $fixtures): Order
    {
        Clock::set(new MockClock($config['panier']['date_creation']));
        $client = $fixtures->getReference('CLIENT', User::class);
        $order = $this->orderService->createOrder(user: $client);
        foreach ($config['panier'] as $cartItem) {
            $this->cartService->addBook(
                user: $client,
                cart: $order->getCart(),
                book: $fixtures->getReference($cartItem['livre'], Book::class),
                quantity: $cartItem['quantite']
            );
        }

        $manager->persist($order);
        $manager->flush();

        return $order;
    }

    public function checkAndPopulateConfig(false|string $configPath): array
    {
        if (false === $configPath) {
            throw new FixtureException(message: 'Incorrect file');
        }

        /** Vérifie les configurations minimales pour les fichiers de pilote de demande */
        $config = Yaml::parseFile($configPath);

        $this->checkConfig($config, $configPath);

        return $this->populateConfig($config);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \App\DataFixtures\Exception\FixtureException
     */
    private function checkConfig(array &$config, string $fileName): void
    {
        $errors = [];
        if (!isset($config['client']['email'])) {
            $errors[] = 'la configuration ne précise pas le champ [client][email]';
        }

        if (!isset($config['client']['date_creation'])) {
            $errors[] = 'la configuration ne précise pas le champ [client][date_creation]';
        }

        if (!isset($config['adresse']['commune'])) {
            $errors[] = 'la configuration ne précise pas le champ [adresse][commune]';
        }

        if (isset($config['actions'])) {
            foreach ($config['actions'] as $action) {
                if (!isset($action['command']) && !isset($action['action'])) {
                    $errors[] = 'Action incorrecte doit être une action ou une commande'.print_r($action, true);
                    continue;
                }
                if (isset($action['command']) && !isset($action['date'])) {
                    $errors[] = 'La date est obligatoire pour la commande  '.$action['command'];
                    continue;
                }
                if (isset($action['action']) && !isset($action['user'], $action['date'])) {
                    $errors[] = 'action incorrectement configurée'.print_r($action, true);
                }
            }
        }

        if (!empty($errors)) {
            throw new FixtureException(errors: $errors, message: "Fichier de configuration $fileName invalide");
        }
    }

    private function populateConfig(array $configFile): array
    {
        foreach ($this->defaultScenar as $keyGroup => $values) {
            foreach ($values as $key => $value) {
                if (!isset($configFile[$keyGroup][$key])) {
                    $configFile[$keyGroup][$key] = $this->defaultScenar[$keyGroup][$key];
                }
            }
        }

        return $configFile;
    }

    public function executeAction(ObjectManager $manager, AppFixtures $param, mixed $action, Order $order): void
    {
        $user = $param->getReference($action['user'], User::class);
        Clock::set(new MockClock($action['date']));
        match ($action['action']) {
            'complete' => $this->orderService->completeOrder($user, $order),
            'pay' => $this->orderService->configurePayment(order: $order, payload: $action['payload']),
            'confirm_provisioning' => $this->orderService->confirmProvisioning($user, $order),
            'prepare' => $this->orderService->prepareOrder($user, $order),
            'ship' => $this->orderService->shipOrder($user, $order, $action['payload']),
            'deliver' => $this->orderService->deliverOrder($user, $order),
            default => throw new FixtureException(message: 'Action inconnue'),
        };
        $manager->flush();
    }
}
