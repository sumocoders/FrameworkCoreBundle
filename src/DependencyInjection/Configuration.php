<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sumo_coders_framework_core');

        $treeBuilder
            ->getRootNode()
            // @mago-expect analysis:non-existent-method(8)
            ->children()
            // @mago-expect analysis:mixed-method-access
            ->arrayNode('sentry_user_context')
            // @mago-expect analysis:mixed-method-access
            ->addDefaultsIfNotSet()
            // @mago-expect analysis:mixed-method-access
            ->children()
            // @mago-expect analysis:mixed-method-access
            ->booleanNode('enabled')
            // @mago-expect analysis:mixed-method-access
            ->defaultTrue()
            // @mago-expect analysis:mixed-method-access
            ->end()
            // @mago-expect analysis:mixed-method-access
            ->end()
            // @mago-expect analysis:mixed-method-access
            ->end()
            // @mago-expect analysis:mixed-method-access
            ->end();

        // @mago-expect analysis:invalid-return-statement
        return $treeBuilder;
    }
}
