<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSecurer
{
    private bool $isDebug;
    private array $cspDirectives;
    private array $extraCspDirectives;
    private string $xFrameOptions;

    public function __construct(
        bool $isDebug,
        array $cspDirectives,
        array $extraCspDirectives,
        string $xFrameOptions
    ) {
        $this->isDebug = $isDebug;
        $this->cspDirectives = $cspDirectives;
        $this->extraCspDirectives = $extraCspDirectives;
        $this->xFrameOptions = $xFrameOptions;
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

            if ($this->xFrameOptions !== '') {
                $event->getResponse()->headers->set('X-Frame-Options', $this->xFrameOptions);
            }

            $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
        }
    }

    private function buildCSPDirectiveString(): string
    {
        $cspDirectives = $this->cspDirectives;

        if (!empty($this->extraCspDirectives)) {
            foreach ($this->extraCspDirectives as $directive => $policies) {
                if (array_key_exists($directive, $cspDirectives)) {
                    $cspDirectives[$directive] = array_unique(array_merge($cspDirectives[$directive], $policies));
                } else {
                    $cspDirectives[$directive] = $policies;
                }
            }
        }

        $policyDirectivesString = '';

        foreach ($cspDirectives as $directive => $policies) {
            $policyDirectivesString .= $directive . ' ' . implode(' ', $policies) . ';' . "\n";
        }

        return $policyDirectivesString;
    }
}
