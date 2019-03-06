<?php

namespace Beelab\TestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('beelab_test');
        $rootNode = \method_exists($treeBuilder, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('beelab_test');

        $rootNode
            ->children()
                ->scalarNode('browser')
                    ->defaultValue('/usr/bin/firefox')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
