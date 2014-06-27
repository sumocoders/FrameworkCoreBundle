<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JsData
 *
 * @package SumoCoders\FrameworkCoreBundle\Service
 */
class JsData
{
    const SPLITCHAR = '.';

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->handleRequestStack();
    }

    /**
     * Handle the request stack
     */
    protected function handleRequestStack()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest) {
            $this->add('request.locale', $currentRequest->getLocale());
        }
    }

    /**
     * Add a value into the array with a given name
     *
     * @param string      $name
     * @param mixed       $value
     * @param null|string $prefix
     */
    protected function add($name, $value, $prefix = null)
    {
        $key = $name;
        if ($prefix) {
            $key = $prefix . self::SPLITCHAR . $name;
        }
        $this->data[$key] = $value;
    }

    /**
     * Get a fallback
     *
     * @param string $key
     * @return null|mixed
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    public function parse()
    {
        return json_encode($this->data);
    }
}
