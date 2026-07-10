<?php

namespace SumoCoders\FrameworkCoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        // @mago-expect analysis:invalid-return-statement
        return new TreeBuilder('sumo_coders_framework_core');
    }
}
