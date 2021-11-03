<?php

namespace Beelab\TestBundle\Tests;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager as LegacyObjectManager;
use Doctrine\Persistence\ObjectManager;

if (\interface_exists(LegacyObjectManager::class)) {
    final class FakeFixture extends AbstractFixture
    {
        public function load(LegacyObjectManager $manager): void
        {
        }
    }
} else {
    final class FakeFixture extends AbstractFixture
    {
        public function load(ObjectManager $manager): void
        {
        }
    }
}
