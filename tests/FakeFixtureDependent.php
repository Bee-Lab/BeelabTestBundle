<?php

namespace Beelab\TestBundle\Tests;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager as LegacyObjectManager;
use Doctrine\Persistence\ObjectManager;

if (\interface_exists(LegacyObjectManager::class)) {
    final class FakeFixtureDependent implements DependentFixtureInterface, FixtureInterface
    {
        public function getDependencies(): array
        {
            return [
                FakeFixture::class,
            ];
        }

        // do not add void return type here! For compatibility...
        public function load(LegacyObjectManager $manager)
        {
        }
    }
} else {
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
}
