<?php

namespace Beelab\TestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class BeelabTestExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('beelab_test.browser', $config['browser']);
        $container->setParameter('beelab_test.firewall', $config['firewall']);
        $container->setParameter('beelab_test.user_service', $config['user_service']);
    }
}
