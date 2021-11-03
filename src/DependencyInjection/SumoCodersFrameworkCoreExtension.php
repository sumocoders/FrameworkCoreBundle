<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SumoCodersFrameworkCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'sumo_coders_framework_core.content_security_policy',
            $config['content_security_policy']
        );
        $container->setParameter(
            'sumo_coders_framework_core.extra_content_security_policy',
            $config['extra_content_security_policy']
        );

        $container->setParameter(
            'sumo_coders_framework_core.x_frame_options',
            $config['x_frame_options']
        );
    }
}
