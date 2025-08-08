<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

readonly class ContentExtension
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicFolder
    ) {
    }

    #[AsTwigFunction('content', isSafe: ['html'])]
    public function getContent(string $path): string
    {
        return file_get_contents($this->publicFolder . $path);
    }
}
