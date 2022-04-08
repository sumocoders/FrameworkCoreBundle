<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultMenuListener
{
    private Security $security;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;

    public function __construct(
        Security $security,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->security = $security;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function enableChildRoutes(ItemInterface $item, string $prefix)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (str_contains($request->get('_route'), $prefix)) {
            $item->setExtra('routes',
                [
                    [
                        'route' => $request->get('_route')
                    ]
                ]
            );
        }
    }

    public function toggleDropdowns(itemInterface $menu)
    {
        $request = $this->requestStack->getCurrentRequest();

        // Loop over all the defined menu items
        foreach ($menu->getChildren() as $child) {
            // If a menu has children -> it has a sub-menu
            if ($child->hasChildren()) {
                foreach ($child->getExtra('routes') as $routes) {
                    // If the current route is inside the sub-menu
                    if ($routes['route'] === $request->get('_route')) {
                        // Toggle the dropdown to active on page load
                        $child->setAttribute('class', 'show');
                        $child->setChildrenAttribute('class', 'show');
                    }
                }
            }
        }
    }
}
