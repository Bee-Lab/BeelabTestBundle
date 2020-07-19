<?php

namespace Beelab\TestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        self::assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }
}
