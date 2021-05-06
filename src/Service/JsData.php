<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JsData
 *
 * @package SumoCoders\FrameworkCoreBundle\Service
 */
class JsData extends ParameterBag
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        parent::__construct();

        $this->requestStack = $requestStack;
    }

    /**
     * Handle the request stack
     */
    protected function handleRequestStack()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest) {
            $requestData = [
                'locale' => $currentRequest->getLocale(),
            ];

            $this->set('request', $requestData);
        }
    }

    /**
     * Parse into string
     *
     * @return string
     */
    public function __toString()
    {
        $this->handleRequestStack();

        return json_encode($this->all());
    }
}
