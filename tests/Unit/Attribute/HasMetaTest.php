<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Attribute\HasMeta;

final class HasMetaTest extends TestCase
{
    public function testDefaults(): void
    {
        $attribute = new HasMeta();

        static::assertSame('getMeta', $attribute->meta);
        static::assertSame('getSlug', $attribute->slug);
    }

    public function testCustomMethods(): void
    {
        $attribute = new HasMeta(meta: 'getMetaData', slug: 'getUrlSlug');

        static::assertSame('getMetaData', $attribute->meta);
        static::assertSame('getUrlSlug', $attribute->slug);
    }

    public function testAttributeTargetsClass(): void
    {
        $reflection = new \ReflectionClass(HasMeta::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        static::assertNotEmpty($attributes);

        $attribute = $attributes[0]->newInstance();
        static::assertSame(\Attribute::TARGET_CLASS, $attribute->flags);
    }
}
