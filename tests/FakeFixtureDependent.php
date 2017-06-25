<?php

namespace Beelab\TestBundle;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class FakeFixtureDependent implements DependentFixtureInterface, FixtureInterface
{
    public function getDependencies()
    {
        return [
            'Beelab\TestBundle\FakeFixture'
        ];
    }

    public function load(ObjectManager $manager)
    {
    }
}
