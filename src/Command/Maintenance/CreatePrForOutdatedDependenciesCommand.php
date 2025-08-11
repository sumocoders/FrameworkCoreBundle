<?php

namespace SumoCoders\FrameworkCoreBundle\Command\Maintenance;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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

    private function runCommand(
        array $command,
        bool $showInput = true,
        bool $showOutput = false,
        bool $returnOutput = false
    ): mixed {
        $io = $this->io;
        if ($showInput) {
            $io->writeln('  → ' . implode(' ', $command));
        }

        $proces = new Process($command);
        if ($showOutput) {
            $output = function ($type, $buffer) use ($io) {
                $lines = explode("\n", $buffer);
                foreach ($lines as $line) {
                    $io->writeln('← ' . $line);
                }
            };
            $proces->run($output);
        } else {
            $proces->run();
        }

        if (!$proces->isSuccessful()) {
            throw new ProcessFailedException($proces);
        }

        if ($returnOutput) {
            return trim($proces->getOutput());
        }

        return null;
    }

    private function runConsoleCommand(
        array $command,
        bool $showInput = true,
        bool $showOutput = false,
        bool $returnOutput = false
    ): mixed {
        $io = $this->io;
        if ($showInput) {
            $commandString = [];
            foreach ($command as $key => $value) {
                if (is_bool($value) && $value === true) {
                    $commandString[] = $key;
                    continue;
                }
                $commandString[] = $value;
            }

            $io->writeln('  → bin/console ' . implode(' ', $commandString));
        }

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $input = new ArrayInput($command);
        $input->setInteractive(false);
        $this->getApplication()->doRun($input, $output);
        $rawOutput = $output->fetch();

        if ($showOutput) {
            $lines = explode("\n", $rawOutput);
            foreach ($lines as $line) {
                $io->writeln('← ' . $line);
            }
        }

        if ($returnOutput) {
            return trim($rawOutput);
        }

        return null;
    }
}
