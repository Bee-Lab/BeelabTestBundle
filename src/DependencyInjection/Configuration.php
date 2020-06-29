<?php

namespace Beelab\TestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('beelab_test');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('browser')
                    ->defaultValue('/usr/bin/firefox')
                ->end()
                ->scalarNode('firewall')
                    ->defaultValue('main')
                ->end()
                ->scalarNode('user_service')
                    ->defaultValue('beelab_user.manager')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
