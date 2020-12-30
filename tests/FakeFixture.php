<?php

namespace Beelab\TestBundle\Tests;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager as LegacyObjectManager;
use Doctrine\Persistence\ObjectManager;

if (\interface_exists(LegacyObjectManager::class)) {
    final class FakeFixture implements FixtureInterface
    {
        public function load(LegacyObjectManager $manager): void
        {
        }
    }
} else {
    final class FakeFixture implements FixtureInterface
    {
        public function load(ObjectManager $manager): void
        {
        }
    }
}
