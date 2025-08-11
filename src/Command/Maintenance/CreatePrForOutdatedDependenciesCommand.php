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

        $this->checkImportmap();

        return Command::SUCCESS;
    }

    private function checkImportmap(): void
    {
        $outdatedPackages = $this->runImportmapOutdatedCommand();
        $semverSafeUpdatePackages = array_filter(
            $outdatedPackages,
            static fn($package) => $package['latest-status'] === 'semver-safe-update'
        );

        if (empty($semverSafeUpdatePackages)) {
            return;
        }

        $this->createPullRequest(
            date('YmdHi') . '-update-importmap-dependencies',
            date('d/m/Y') . ' Update importmap dependencies',
            [$this, 'runImportmapUpdateCommands'],
            [$semverSafeUpdatePackages]
        );
    }

    private function createPullRequest(
        string $newBranchName,
        string $pullRequestTitle,
        callable $commandsToRun,
        array $arguments = []
    ): void {
        // get current branch name
        $currentBranch = $this->runCommand(
            ['git', 'rev-parse', '--abbrev-ref', 'HEAD'],
            false,
            false,
            true
        );

        // create new branch
        $this->runCommand(
            ['git', 'checkout', '-b', $newBranchName],
        );

        // run actual commands
        call_user_func_array($commandsToRun, $arguments);

        // push to remote
        $this->runCommand(
            [
                'git',
                'push',
                '-o merge_request.create',
                '-o merge_request.remove_source_branch',
                '-o merge_request.target=' . $currentBranch,
                '-o merge_request.title=' . $pullRequestTitle,
                '-o merge_request.description=This is an automated merge request. Please review the changes.',
            ],
            true,
            true,
        );

        // go back to original branch
        $this->runCommand(
            ['git', 'checkout', $currentBranch],
            false,
            false,
        );
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

    /**
     * @return array<int, array{
     *     'name': string,
     *     'current': string,
     *     'latest': string,
     *     'latest-status': string
     * }>
     */
    private function runImportmapOutdatedCommand(): array
    {
        $output = $this->runConsoleCommand(
            [
                'command' => 'importmap:outdated',
                '--no-interaction' => true,
                '--format' => 'json',
            ],
            true,
            false,
            true
        );

        if ($output === '') {
            return [];
        }

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    private function runImportmapUpdateCommands(array $packages): void
    {
        foreach ($packages as $package) {
            $this->runConsoleCommand(
                [
                    'command' => 'importmap:update',
                    $package['name'],
                    '--no-interaction' => true,
                    '--ansi' => true,
                ],
                true,
                true
            );

            $this->runCommand(['git', 'add', 'importmap.php']);
            $commitMessage = sprintf(
                'chore(importmap): Update %1$s (%2$s → %3$s)',
                $package['name'],
                $package['current'],
                $package['latest']
            );
            $this->runCommand(['git', 'commit', '-nm', $commitMessage]);
        }
    }
}
