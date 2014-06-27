<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;

class DefaultMenuListener implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
