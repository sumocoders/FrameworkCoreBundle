<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class JsData extends ParameterBag
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        parent::__construct();

        $this->requestStack = $requestStack;
    }

    protected function handleRequestStack(): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest) {
            $requestData = [
                'locale' => $currentRequest->getLocale(),
            ];

            $this->set('request', $requestData);
        }
    }

    public function __toString(): string
    {
        $this->handleRequestStack();

        return json_encode($this->all());
    }
}
