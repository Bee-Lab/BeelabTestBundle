<?php

namespace Beelab\TestBundle\DependencyInjection;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $configuration->getConfigTreeBuilder());
    }
}
