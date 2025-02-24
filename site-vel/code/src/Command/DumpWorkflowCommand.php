<?php

namespace App\Command;

use App\Dumper\WorkflowDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsCommand(
    name: 'app:DumpWorkflow',
    description: 'Cette commande permet d\'exporter le workflow au format Mermaid'
)]
class DumpWorkflowCommand extends Command
{
    public const array VALID_WORKFLOW_NAMES = ['vel_order'];

    public function __construct(
        private readonly WorkflowInterface $velOrderStateMachine,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'representation',
            shortcut: 'r',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Modes de représentation séparés par des virgules'
        );
        $this->addOption(
            name: 'ignore-places',
            shortcut: 'i',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Liste des places à ignorer'
        );
        $this->addOption(
            name: 'only-places',
            shortcut: 'o',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Liste des places à mettre en évidence. Neutralise les autres options.'
        );
        $this->addOption(
            name: 'workflow-name',
            shortcut: 'w',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Nom du workflow.',
            default: 'vel_order'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dumper = new WorkflowDumper(
            transitionType: WorkflowDumper::TRANSITION_TYPE_STATEMACHINE,
            presentation: $input->getOption('representation'),
            ignorePlaces: $input->getOption('ignore-places'),
            onlyPlaces: $input->getOption('only-places')
        );

        $workflowName = $input->getOption('workflow-name');

        $this->validateWorkflowName($workflowName);

        $output->writeln($dumper->dump($this->velOrderStateMachine->getDefinition()));


        return Command::SUCCESS;
    }

    private function validateWorkflowName(string $name): void
    {
        if (!in_array($name, self::VALID_WORKFLOW_NAMES, true)) {
            throw new InvalidArgumentException("Le workflow '$name' n'existe pas.");
        }
    }
}
