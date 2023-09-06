<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use SumoCoders\FrameworkCoreBundle\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class PaginatorRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Environment $twig,
        private RequestStack $requestStack,
    ) {
    }

    public function renderPagination(Paginator $paginator): string
    {
        $request = $this->getRequest();

        if (null !== $this->requestStack->getParentRequest()) {
            throw new \RuntimeException('We can not guess the route when used in a sub-request');
        }

        $route = $request->attributes->get('_route');

        // Make sure we read the route parameters from the passed option array
        $routeParams = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

        $paginator->calculateStartAndEndPage();

        return $this->twig->load('@SumoCodersFrameworkCore/Twig/pagination.html.twig')->renderBlock(
            'pager',
            [
                'paginator' => $paginator,
                'route' => $route,
                'routeParams' => $routeParams,
                'current_page' => $paginator->getCurrentPage(),
                'start_page' => $paginator->getStartPage(),
                'end_page' => $paginator->getEndPage(),
                'page_count' => $paginator->getNumberOfPages(),
            ]
        );
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
