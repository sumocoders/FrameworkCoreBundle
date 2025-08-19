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
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'sumo:maintenance:create-pr-for-outdated-dependencies',
    description: 'Create PR for outdated dependencies (Importmap and Composer)',
)]
class CreatePrForOutdatedDependenciesCommand extends Command
{
    private SymfonyStyle $io;

    /**
     * @var array<int,array{
     *     id: int,
     *     title: string,
     *     target_branch: string}
     *     >
     */
    private array $openMergeRequests = [];

    private ?string $originalBranch = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->openMergeRequests = $this->listOpenMergeRequests();

        $this->checkImportmap();
        $this->checkComposer();

        return Command::SUCCESS;
    }

    private function checkImportmap(): void
    {
        if ($this->hasMergeRequest('Update importmap dependencies', $this->getOriginalBranch())) {
            $this->io->warning('There is already a merge request for updating importmap dependencies.');

            return;
        }

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
            return;
        }

        $outdatedPackages = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
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

        $this->io->success('Created a merge request for updating importmap dependencies.');
    }

    /**
     * @param array<int,array{
     *   name: string,
     *   current: string,
     *   latest: string,
     *   }> $packages
     */
    private function runImportmapUpdateCommands(array $packages): void
    {
        $versionMessages = [];
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
            $versionMessages[] = sprintf(
                '  * %1$s (%2$s → %3$s)',
                $package['name'],
                $package['current'],
                $package['latest']
            );
        }

        $commitMessage = 'chore(importmap): Update importmap dependencies' .
                         "\n\n" . implode("\n", $versionMessages);
        $this->runCommand(
            [
                'git',
                'commit',
                '--allow-empty',
                '--no-verify',
                '--message',
                $commitMessage,
            ]
        );
    }

    private function checkComposer(): void
    {
        if ($this->hasMergeRequest('Update composer dependencies', $this->getOriginalBranch())) {
            $this->io->warning('There is already a merge request for updating composer dependencies.');

            return;
        }

        $outdatedPackages = $this->runCommand(
            ['composer', 'outdated', '--direct', '--minor-only', '--no-scripts', '--format=json'],
            true,
            false,
            true
        );

        if ($outdatedPackages === '') {
            return;
        }

        $outdatedPackages = json_decode($outdatedPackages, true, 512, JSON_THROW_ON_ERROR);
        if (!array_key_exists('installed', $outdatedPackages)) {
            return;
        }

        $semverSafeUpdatePackages = array_filter(
            $outdatedPackages['installed'],
            static function ($package) {
                if ($package['abandoned']) {
                    return false;
                }

                return $package['latest-status'] === 'semver-safe-update';
            }
        );

        if (empty($semverSafeUpdatePackages)) {
            return;
        }

        $this->createPullRequest(
            date('YmdHi') . '-update-composer-dependencies',
            date('d/m/Y') . ' Update composer dependencies',
            [$this, 'runComposerUpdateCommands'],
            [$semverSafeUpdatePackages]
        );

        $this->io->success('Created a merge request for updating composer dependencies.');
    }

    /**
     * @param array<int,array{
     *   name: string,
     *   version: string,
     *   latest: string,
     *   }> $packages
     */
    private function runComposerUpdateCommands(array $packages): void
    {
        $symfonyBinary = $this->findCommand('symfony');
        $versionMessages = [];
        foreach ($packages as $package) {
            $command = [
                'composer',
                'update',
                $package['name'],
                '--no-interaction',
                '--ansi',
                '--no-audit',
                '--no-scripts',
                '--no-progress',
                '--no-plugins',
            ];

            // prepend symfony binary if available
            if ($symfonyBinary !== false) {
                array_unshift($command, $symfonyBinary);
            }

            $this->runCommand(
                $command,
                true,
                true
            );

            $this->runCommand(['git', 'add', 'composer.json', 'composer.lock', 'symfony.lock']);
            $versionMessages[] = sprintf(
                '  * %1$s (%2$s → %3$s)',
                $package['name'],
                $package['version'],
                $package['latest']
            );
        }

        $commitMessage = 'chore(importmap): Update importmap dependencies' .
                         "\n\n" . implode("\n", $versionMessages);
        $this->runCommand(
            [
                'git',
                'commit',
                '--allow-empty',
                '--no-verify',
                '--message',
                $commitMessage,
            ]
        );
    }

    /**
     * @param callable|array{CreatePrForOutdatedDependenciesCommand,string} $commandsToRun
     * @param array<mixed>                                                  $arguments
     */
    private function createPullRequest(
        string $newBranchName,
        string $pullRequestTitle,
        callable|array $commandsToRun,
        array $arguments = []
    ): void {
        // create new branch
        $this->runCommand(
            ['git', 'checkout', '-b', $newBranchName],
        );

        // run actual commands
        if (!is_callable($commandsToRun)) {
            throw new \InvalidArgumentException('The $commandsToRun parameter must be a callable.');
        }
        call_user_func_array($commandsToRun, $arguments);

        // push to remote
        $this->runCommand(
            [
                'git',
                'push',
                '-o merge_request.create',
                '-o merge_request.remove_source_branch',
                '-o merge_request.target=' . $this->getOriginalBranch(),
                '-o merge_request.title=' . $pullRequestTitle,
                '-o merge_request.description=This is an automated merge request. Please review the changes.',
            ],
            true,
            true,
        );

        // go back to original branch
        $this->runCommand(
            ['git', 'checkout', $this->getOriginalBranch()],
            false,
            false,
        );
    }

    /**
     * @param array<mixed,mixed> $command
     */
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

        $process = new Process($command);
        if ($showOutput) {
            $output = function ($type, $buffer) use ($io) {
                $buffer = trim($buffer);
                $lines = explode("\n", $buffer);
                foreach ($lines as $line) {
                    $io->writeln('  ← ' . $line);
                }
            };
            $process->run($output);
        } else {
            $process->run();
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if ($returnOutput) {
            return trim($process->getOutput());
        }

        return null;
    }

    /**
     * @param array<mixed,mixed> $command
     */
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
                $io->writeln('  ← ' . $line);
            }
        }

        if ($returnOutput) {
            return trim($rawOutput);
        }

        return null;
    }

    private function findCommand(string $command): string|bool
    {
        $process = new Process(['which', $command]);
        $process->run();

        if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
            return trim($process->getOutput());
        }

        return false;
    }

    /**
     * @return array<int,array{
     *     id: int,
     *     title: string,
     *     target_branch: string}
     *     >
     */
    private function listOpenMergeRequests(): array
    {
        $gitlabUrl = getenv('CI_API_V4_URL');
        if ($gitlabUrl === false) {
            $gitlabUrl = 'https://git.sumocoders.be/api/v4';
        }
        $projectId = getenv('CI_PROJECT_ID');
        if ($projectId === false) {
            $output = $this->runCommand(
                ['git', 'config', '--get', 'remote.origin.url'],
                false,
                false,
                true
            );
            $matches = [];
            preg_match('/.*:(.+)\.git$/', $output, $matches);

            if (!isset($matches[1])) {
                throw new \RuntimeException(
                    'Could not determine project ID from git remote URL.'
                );
            }

            $projectId = urlencode($matches[1]);
        }
        $gitlabToken = getenv('GITLAB_ACCESS_TOKEN');
        if ($gitlabToken === false) {
            $gitlabToken = getenv('SUMO_GITLAB_ACCESS_TOKEN');
        }
        if ($gitlabToken === false) {
            throw new \RuntimeException(
                'You need to set the SUMO_GITLAB_ACCESS_TOKEN environment variable.'
            );
        }

        $response = $this->httpClient->request(
            'GET',
            sprintf(
                '%1$s/projects/%2$s/merge_requests',
                $gitlabUrl,
                $projectId
            ),
            [
                'headers' => [
                    'PRIVATE-TOKEN' => $gitlabToken,
                ],
                'query' => [
                    'state' => 'opened',
                    'per_page' => 100,
                ],
            ]
        );

        $data = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (empty($data)) {
            return [];
        }

        return array_map(function ($mergeRequest) {
            return [
                'id' => $mergeRequest->id,
                'title' => $mergeRequest->title,
                'target_branch' => $mergeRequest->target_branch,
            ];
        }, $data);
    }

    private function hasMergeRequest(string $title, string $targetBranch): bool
    {
        foreach ($this->openMergeRequests as $mergeRequest) {
            if ($mergeRequest['target_branch'] === $targetBranch) {
                if (stripos($mergeRequest['title'], $title) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getOriginalBranch(): string
    {
        if ($this->originalBranch !== null) {
            return $this->originalBranch;
        }

        $branch = getenv('CI_COMMIT_BRANCH');
        if ($branch === false) {
            $branch = $this->runCommand(
                ['git', 'rev-parse', '--abbrev-ref', 'HEAD'],
                false,
                false,
                true
            );
        }
        $this->originalBranch = trim($branch);

        return $this->originalBranch;
    }
}
