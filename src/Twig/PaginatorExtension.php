<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PaginatorExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pagination', [PaginatorRuntime::class, 'renderPagination'], ['is_safe' => ['html']]),
        ];
    }
}
