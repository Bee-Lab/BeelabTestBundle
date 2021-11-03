<?php

namespace Beelab\TestBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AbstractContainerAwareFixture.
 */
abstract class AbstractContainerAwareFixture extends AbstractFixture implements ContainerAwareInterface
{
    protected ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }
}
