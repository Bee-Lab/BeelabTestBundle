<?php

namespace Beelab\TestBundle\Tests;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FakeFixtureDependent implements DependentFixtureInterface, FixtureInterface
{
    public function getDependencies(): array
    {
        return [
            FakeFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
    }
}
