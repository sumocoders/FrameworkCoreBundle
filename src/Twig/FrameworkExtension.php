<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FrameworkExtension extends AbstractExtension
{
    public function __construct(private RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ucfirst', 'ucfirst'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme', [$this, 'determineTheme']),
            new TwigFunction('sidebarIsOpen', [$this, 'sidebarIsOpen']),
        ];
    }

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
