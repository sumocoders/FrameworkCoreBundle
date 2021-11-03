<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSecurer
{
    private bool $isDebug;
    private array $cspDirectives;

    public function __construct(bool $isDebug, array $cspDirectives)
    {
        $this->isDebug = $isDebug;
        $this->cspDirectives = $cspDirectives;
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
            if (!empty($this->cspDirectives)) {
                $event->getResponse()->headers->set(
                    'Content-Security-Policy',
                    $this->buildCSPDirectiveString()
                );
            }

            $event->getResponse()->headers->set('X-Frame-Options', 'deny');
            $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
        }
    }

    private function buildCSPDirectiveString(): string
    {
        $cspDirectives = $this->cspDirectives;
        $policyDirectivesString = '';

        foreach ($cspDirectives as $directive => $policies) {
            $policyDirectivesString .= $directive . ' ' . implode(' ', $policies) . ';' . "\n";
        }

        return $policyDirectivesString;
    }
}
