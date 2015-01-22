<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Symfony\Component\Security\Core\SecurityContext;

class DefaultMenuListener
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * @param SecurityContext $securityContext
     */
    public function setSecurityContext(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }
}
