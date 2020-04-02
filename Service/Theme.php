<?php

namespace Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Theme
{
    /** @var Request */
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function current(): string
    {
        if ($this->request->cookies->has('theme')) {
            return 'theme-light';
        }

        return 'theme-' . $this->request->cookies->get('theme');
    }
}
