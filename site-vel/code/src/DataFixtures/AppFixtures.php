<?php

namespace App\DataFixtures;

use App\DataFixtures\Exception\FixtureException;
use App\DataFixtures\Service\BaseObjetFixtureService;
use App\DataFixtures\Service\OrderFixtureService;
use App\DataFixtures\Service\UserFixtureService;
use App\Kernel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class AppFixtures extends Fixture
{
    public SymfonyStyle $io;
    protected string $scenarioDir;
    protected string $booksDir;
    protected string $authorsDir;
    private array $errors = [];

    public function __construct(
        ParameterBagInterface $parameterBag,
        private readonly UserFixtureService $userFixtureService,
        private readonly OrderFixtureService $orderFixtureService,
        private readonly BaseObjetFixtureService $baseObjetFixtureService,
        Kernel $kernel,
    ) {
        $this->io = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());
        $this->scenarioDir = $parameterBag->get('app.fixtures.config_scenarii').'/scenarios';
        $this->booksDir = $parameterBag->get('app.fixtures.config_scenarii').'/books';
        $this->authorsDir = $parameterBag->get('app.fixtures.config_scenarii').'/authors';
    }

    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->sortByName(true);

        $this->loadAuthors($finder, $manager);
        $this->loadBooks($finder, $manager);

        $this->loadScenarios($finder, $manager);
    }

    private function loadAuthors(Finder $finder, ObjectManager $manager): void
    {
        foreach ($finder->in($this->authorsDir) as $file) {
            if ($file->isFile()) {
                $this->baseObjetFixtureService->createAuthor($this, $manager, $confi);
            }
        }
        $manager->flush();
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     * @throws FixtureException
     */
    private function loadScenarios(Finder $finder, ObjectManager $manager): void
    {
        foreach ($finder->in($this->scenarioDir) as $file) {
            $orderConfig = Yaml::parseFile($file->getRealPath());
            Clock::set(new MockClock($orderConfig['client']['date_creation']));

            $customer = $this->userFixtureService->createCustomer($orderConfig);
            $this->setReference('CLIENT', $customer);

            $order = $this->orderFixtureService->createOrder($manager, $orderConfig, $this);
            foreach ($orderConfig['actions'] as $action) {
                $this->orderFixtureService->executeAction($manager, $this, $action, $order);
            }
            if (!empty($this->errors)) {
                $this->io->error("Les fixtures suivantes sont en erreur :\n - ".implode("\n - ", $this->errors));
            }

            $manager->flush();
        }
    }

    private function loadBooks(Finder $finder, ObjectManager $manager)
    {
        foreach ($finder->in($this->booksDir) as $file) {
            if ($file->isFile()) {
                $config = Yaml::parseFile($file->getRealPath());
                $this->baseObjetFixtureService->createBook($this, $manager, $config);
            }
        }
        $manager->flush();
    }
}
