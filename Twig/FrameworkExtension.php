<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class FrameworkExtension extends \Twig_Extension
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the registered functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'bundleExists',
                array($this, 'bundleExists')
            )
        );
    }

    /**
     * Check if a bundle exists
     *
     * @param string $bundle
     * @return bool
     */
    public function bundleExists($bundle)
    {
        $bundles = $this->container->getParameter('kernel.bundles');

        return array_key_exists($bundle, $bundles);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'framework_extension';
    }
}
