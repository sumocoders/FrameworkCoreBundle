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
            $item->setExtra(
                'routes',
                [
                    [
                        'route' => $request->get('_route'),
                    ],
                ]
            );
        }
    }
}
