<?php

namespace SumoCoders\FrameworkCoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TranslateCommand extends Command
{
    private ParameterBagInterface $parameters;

    public function __construct(ParameterBagInterface $parameters)
    {
        parent::__construct();
        $this->setName('sumo:translate');
        $this->parameters = $parameters;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $locale = $this->parameters->get('locale');

        $command = $this->getApplication()->find('translation:update');

        $arguments = [
            'locale' => $locale,
            '--force' => true,
            '--format' => 'yaml',
        ];

        $translationInput = new ArrayInput($arguments);

        return $command->run($translationInput, $output);
    }
}
