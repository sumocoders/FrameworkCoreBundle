<?php

namespace SumoCoders\FrameworkCoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'secrets:get'
)]
class SecretsGetCommand extends Command
{
    private function __construct(
        private AbstractVault $vault
    ){
    }

    public function configure()
    {
        $this->setDescription('Get a secret from the vault');
        $this->addArgument('key', InputArgument::REQUIRED, 'The key to get from the vault');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');

        $output->writeln(
            $this->vault->reveal($key)
        );

        return Command::SUCCESS;
    }
}