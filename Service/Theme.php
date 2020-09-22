<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Theme
{
    /** @var Request */
    private $request;

    /** @var JsData */
    private $jsData;

    /** @var Packages */
    private $packages;

    public function __construct(RequestStack $requestStack, JsData $jsData, Packages $packages)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->jsData = $jsData;
        $this->packages = $packages;

        $this->makeDataAvailableForJS();
    }

    private function makeDataAvailableForJS(): void
    {
        $this->jsData->set(
            'theme',
            [
                'paths' => [
                    'dark' => $this->packages->getUrl('build/style-dark.css'),
                ],
            ]
        );
    }

    public function current(): string
    {
        // no request available, when called thru a cli or such
        if (is_null($this->request)) {
            return 'theme-light';
        }

        if (!$this->request->cookies->has('theme')) {
            return 'theme-light';
        }

        return 'theme-' . $this->request->cookies->get('theme');
    }
}
