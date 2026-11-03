<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SumoCodersFrameworkCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // AuditLogger has #[Target('audit_trail.logger')], which fails to compile when the channel doesn't exist
        if ($container->hasExtension('monolog')) {
            $container->prependExtensionConfig('monolog', ['channels' => ['audit_trail']]);
        }
    }

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

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'FrameworkCoreBundle' => [
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/../Entity',
                        'prefix' => 'SumoCoders\FrameworkCoreBundle\Entity',
                        'alias' => 'FrameworkCoreBundle',
                    ],
                ],
            ],
        ]);
    }
}
