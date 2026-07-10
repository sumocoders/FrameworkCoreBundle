<?php

namespace SumoCoders\FrameworkCoreBundle\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'sumo:translate')]
final class TranslateCommand
{
    public function __construct(
        private readonly ParameterBagInterface $parameters,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        Application $application,
    ): int {
        $locales = $this->parameters->get('locales');

        // phpcs:ignore Generic.Files.LineLength.TooLong
        // @mago-expect analysis:mixed-assignment,possibly-invalid-iterator,non-iterable-object-iteration,possibly-null-iterator
        // @phpstan-ignore foreach.nonIterable
        foreach ($locales as $locale) {
            $input = new ArrayInput([
                'command' => 'translation:extract',
                'locale' => $locale,
                '--force' => true,
                '--format' => 'yaml',
            ]);

            $input->setInteractive(false);

            $application->doRun($input, $output);
        }

        return Command::SUCCESS;
    }
}
