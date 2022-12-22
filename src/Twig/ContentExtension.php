<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicFolder
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('content', [$this, 'getContent'], ['is_safe' => ['html']]),
        ];
    }

    public function getContent(string $path): string
    {
        return file_get_contents($this->publicFolder . $path);
    }
}
