<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FrameworkExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ucfirst','ucfirst'),
            new TwigFunction('theme', [$this, 'determineTheme']),
        ];
    }

    public function determineTheme(): string
    {
        // no request available, when called thru a cli or such
        if (is_null($this->requestStack->getCurrentRequest())) {
            return 'theme-light';
        }

        if (!$this->requestStack->getCurrentRequest()->cookies->has('theme')) {
            return 'theme-light';
        }

        return 'theme-' . $this->requestStack->getCurrentRequest()->cookies->get('theme');
    }
}
