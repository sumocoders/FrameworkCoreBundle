<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SumoCodersFrameworkCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config'),
        );
        $loader->load('services.php');

        // An #[AutoconfigureTag] on the interface only works for classes found by resource scanning, which this bundle
        // doesn't do; register it here so the bundle's providers and the application's are both tagged.
        $container->registerForAutoconfiguration(QuickSearchProvider::class)->addTag(QuickSearchProvider::class);
    }
}
