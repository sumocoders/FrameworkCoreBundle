<?php

namespace SumoCoders\FrameworkCoreBundle\Intl;

use Symfony\Component\Intl\ResourceBundle;

class BelgiumPostCodes extends ResourceBundle
{
    /**
     * @return array<string, string>
     */
    public static function getNames(): array
    {
        // @mago-expect analysis:mixed-return-statement
        return self::readEntry(['Names'], 'nl', false);
    }

    protected static function getPath(): string
    {
        return __DIR__ . '/Resources/data/belgiumpostcodes';
    }
}
