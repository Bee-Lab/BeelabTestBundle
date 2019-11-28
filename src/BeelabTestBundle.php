<?php

namespace Beelab\TestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BeelabTestBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
