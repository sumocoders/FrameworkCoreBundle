<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sumo_coders_framework_core');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->arrayNode('mcp_tokens')
            ->info('Personal bearer tokens for an MCP endpoint, off until user_class is set.')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('user_class')
            ->info('The application\'s security user entity, the owner of a token.')
            ->defaultNull()
            ->validate()
            ->ifTrue(
                static fn (mixed $class): bool => (
                    $class !== null
                    && (!is_string($class) || !is_subclass_of($class, UserInterface::class))
                ),
            )
            ->thenInvalid('%s is not a class implementing ' . UserInterface::class . '.')
            ->end()
            ->end()
            ->scalarNode('token_prefix')
            ->info('Prepended to every new token, so secret scanners can recognize it.')
            ->defaultValue('mcp_')
            ->end()
            ->end()
            ->end()
            ->end();

        // @mago-expect analysis:invalid-return-statement
        return $treeBuilder;
    }
}
