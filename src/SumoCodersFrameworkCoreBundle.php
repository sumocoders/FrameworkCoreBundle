<?php

namespace SumoCoders\FrameworkCoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SumoCodersFrameworkCoreBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
