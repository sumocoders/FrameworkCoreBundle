<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSecurer
{
    private bool $isDebug;

    public function __construct(bool $isDebug)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * Add some headers to the response to make our application more secure
     * see https://owasp.org/www-project-secure-headers
     *
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        /*
         * We only send the security headers when we're not in dev mode
         */
        if (!$this->isDebug) {
            $event->getResponse()->headers->set('Content-Security-Policy',
                "default-src 'self';" . // Default rule: only allow content from our own domain
                "frame-src 'none';" // Block all iframes
            );

            $event->getResponse()->headers->set('X-Frame-Options', 'deny');
            $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
        }
    }
}
