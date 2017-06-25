<?php

namespace Beelab\TestBundle;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class FakeFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
    }
}
