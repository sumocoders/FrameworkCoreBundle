<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Sinergi\BrowserDetector\Browser;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class FrameworkRuntime implements RuntimeExtensionInterface
{
    private Browser $browser;
    private Environment $twig;

    public function __construct(
        Browser $browser,
        Environment $twig
    ) {
        $this->browser = $browser;
        $this->twig = $twig;
    }

    public function checkBrowser(): ?string
    {
        if ($this->browser->getName() === Browser::FIREFOX && $this->browser->getVersion() < 60 ||
            $this->browser->getName() === Browser::IE && $this->browser->getVersion() < 12 ||
            $this->browser->getName() === Browser::CHROME && $this->browser->getVersion() < 60 ||
            $this->browser->getName() === Browser::SAFARI && $this->browser->getVersion() < 12) {
            return $this->twig->render(
                'browser.html',
                [
                    'message' => 'You\'re using an outdated browser.'
                ]
            );
        }

        return null;
    }
}
