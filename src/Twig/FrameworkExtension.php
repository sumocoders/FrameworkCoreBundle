<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

readonly class FrameworkExtension
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[AsTwigFilter('ucfirst')]
    public static function ucfirst(string $string): string
    {
        return ucfirst($string);
    }

    #[AsTwigFunction('theme')]
    public function determineTheme(): string
    {
        if (is_null($this->requestStack->getCurrentRequest())) {
            return 'theme-light';
        }

        if (!$this->requestStack->getCurrentRequest()->cookies->has('theme')) {
            return 'theme-light';
        }

        return 'theme-' . $this->requestStack->getCurrentRequest()->cookies->get('theme');
    }

    #[AsTwigFunction('sidebarIsOpen')]
    public function sidebarIsOpen(): bool
    {
        if (is_null($this->requestStack->getCurrentRequest())) {
            return true;
        }

        if (!$this->requestStack->getCurrentRequest()->cookies->has('sidebar_is_open')) {
            return true;
        }

        return $this->requestStack->getCurrentRequest()->cookies->get('sidebar_is_open') !== 'false';
    }
}
