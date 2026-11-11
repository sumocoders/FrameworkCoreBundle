<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Security\Core\User\UserInterface;

class SumoCodersFrameworkCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // AuditLogger has #[Target('audit_trail.logger')], which fails to compile when the channel doesn't exist
        if ($container->hasExtension('monolog')) {
            $container->prependExtensionConfig('monolog', ['channels' => ['audit_trail']]);
        }

        $this->prependMcpTokenMapping($container);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter(
            'sumo_coders_framework_core.mcp_tokens.token_prefix',
            $this->processMcpTokenConfiguration($configs)['token_prefix'],
        );

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

    /**
     * McpToken belongs to the application's user class, which Doctrine only knows once mcp_tokens.user_class is set.
     * Without it the entity stays unmapped: an unresolved UserInterface relation would break the schema tool and
     * the proxy cache warmer of every application that doesn't use MCP.
     */
    private function prependMcpTokenMapping(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine')) {
            return;
        }

        $userClass = $this->processMcpTokenConfiguration(
            $container->getExtensionConfig($this->getAlias()),
        )['user_class'];
        if ($userClass === null) {
            return;
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'resolve_target_entities' => [UserInterface::class => $userClass],
                'mappings' => [
                    'FrameworkCoreBundleMcp' => [
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/../Mcp/Entity',
                        'prefix' => 'SumoCoders\FrameworkCoreBundle\Mcp\Entity',
                        'alias' => 'FrameworkCoreBundleMcp',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array<array<mixed>> $configs
     *
     * @return array{user_class: class-string<UserInterface>|null, token_prefix: string}
     */
    private function processMcpTokenConfiguration(array $configs): array
    {
        /** @var array{mcp_tokens: array{user_class: class-string<UserInterface>|null, token_prefix: string}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        return $config['mcp_tokens'];
    }
}
