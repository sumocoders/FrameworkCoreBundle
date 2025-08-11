<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Attribute\AsTwigFunction;

readonly class ContentExtension
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicFolder,
        private Filesystem $filesystem
    ) {
    }

    #[AsTwigFunction('content', isSafe: ['html'])]
    public function getContent(string $path): string
    {
        return $this->filesystem->readFile($this->publicFolder . $path);
    }
}
