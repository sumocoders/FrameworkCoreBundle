<?php

namespace SumoCoders\FrameworkCoreBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'sumo:translate'
)]
class TranslateCommand extends Command
{
    public function __construct(private readonly ParameterBagInterface $parameters)
    {
        parent::__construct();
    }

    public function __invoke(OutputInterface $output): int
    {
        $locales = $this->parameters->get('locales');

        $command = $this->getApplication()->find('translation:extract');

        foreach ($locales as $locale) {
            $arguments = [
                'locale' => $locale,
                '--force' => true,
                '--format' => 'yaml',
            ];

            $translationInput = new ArrayInput($arguments);

            $command->run($translationInput, $output);
        }

        return Command::SUCCESS;
    }
}
