<?php

namespace SumoCoders\FrameworkCoreBundle\Command\Maintenance;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sumo:maintenance:create-pr-for-outdated-dependencies',
    description: 'Create PR for outdated dependencies (Importmap)',
)]
class CreatePrForOutdatedDependenciesCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        return Command::SUCCESS;
    }
}
