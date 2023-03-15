<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sumo_coders_framework_core');

        $treeBuilder->getRootNode()
            ->children()
                ->variableNode('content_security_policy')
                    ->defaultValue([
                        'default-src' => [
                            "'self'",   // Default rule: only allow content from our own domain
                        ],
                        'style-src' => [
                            "'self'",
                        ],
                        'font-src' => [
                            "'self'",
                        ],
                        'img-src' => [
                            "'self'",
                            "data:", // Bootstrap icons
                        ],
                        'child-src' => [
                            "'none'", // Block all iframes
                        ],
                        'script-src' => [
                            "'self'",
                        ],
                    ])
                ->end()
                ->variableNode('extra_content_security_policy')
                    ->defaultValue([])
                ->end()
                ->enumNode('x_frame_options')
                    ->values(['', 'deny', 'sameorigin'])
                    ->defaultValue('deny')
                ->end()
                ->enumNode('x_content_type_options')
                    ->values(['', 'nosniff'])
                    ->defaultValue('nosniff')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
