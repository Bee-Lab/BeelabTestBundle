<?php

namespace Beelab\TestBundle\Tests;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

final class FakeFixtureDependent implements DependentFixtureInterface, FixtureInterface
{
    public function getDependencies(): array
    {
        return [
            'Beelab\TestBundle\FakeFixture',
        ];
    }

    public function load(ObjectManager $manager): void
    {
    }
}
