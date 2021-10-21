<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FrameworkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ucfirst','ucfirst'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('browser_check', [FrameworkRuntime::class, 'checkBrowser'], ['is_safe' => ['html']]),
        ];
    }
}
