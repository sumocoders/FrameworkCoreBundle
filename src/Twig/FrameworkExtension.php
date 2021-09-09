<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FrameworkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ucfirst','ucfirst'),
        ];
    }
}
