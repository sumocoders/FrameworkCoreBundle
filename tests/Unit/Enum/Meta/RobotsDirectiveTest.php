<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Enum\Meta;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;
use ValueError;

final class RobotsDirectiveTest extends TestCase
{
    public function testCaseValues(): void
    {
        static::assertSame('index', RobotsDirective::Index->value);
        static::assertSame('noindex', RobotsDirective::NoIndex->value);
        static::assertSame('follow', RobotsDirective::Follow->value);
        static::assertSame('nofollow', RobotsDirective::NoFollow->value);
        static::assertSame('noarchive', RobotsDirective::NoArchive->value);
        static::assertSame('nosnippet', RobotsDirective::NoSnippet->value);
        static::assertSame('noimageindex', RobotsDirective::NoImageIndex->value);
    }

    public function testFromValidValue(): void
    {
        static::assertSame(RobotsDirective::Index, RobotsDirective::from('index'));
        static::assertSame(RobotsDirective::NoIndex, RobotsDirective::from('noindex'));
        static::assertSame(RobotsDirective::Follow, RobotsDirective::from('follow'));
        static::assertSame(RobotsDirective::NoFollow, RobotsDirective::from('nofollow'));
        static::assertSame(RobotsDirective::NoArchive, RobotsDirective::from('noarchive'));
        static::assertSame(RobotsDirective::NoSnippet, RobotsDirective::from('nosnippet'));
        static::assertSame(RobotsDirective::NoImageIndex, RobotsDirective::from('noimageindex'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(ValueError::class);
        RobotsDirective::from('invalid');
    }
}
