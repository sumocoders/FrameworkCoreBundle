<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;
use SumoCoders\FrameworkCoreBundle\Service\MetaContext;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use SumoCoders\FrameworkCoreBundle\Twig\MetaExtension;
use ReflectionMethod;
use Twig\Attribute\AsTwigFunction;

final class MetaExtensionTest extends TestCase
{
    private MetaContext $metaContext;
    private MetaExtension $extension;

    protected function setUp(): void
    {
        $this->metaContext = new MetaContext($this->createStub(PageTitle::class));
        $this->extension = new MetaExtension($this->metaContext);
    }

    public function testReturnsEmptyStringWhenNoMetaInContext(): void
    {
        static::assertSame('', $this->extension->metaTags());
    }

    public function testRendersMetaDescription(): void
    {
        $meta = new Meta();
        $meta->setMetaDescription('My page description');
        $this->metaContext->setMeta($meta);

        $html = $this->extension->metaTags();

        static::assertStringContainsString('name="description"', $html);
        static::assertStringContainsString('My page description', $html);
    }

    public function testRendersRobotsDirective(): void
    {
        $meta = new Meta();
        $meta->setRobots([RobotsDirective::NoIndex]);
        $this->metaContext->setMeta($meta);

        $html = $this->extension->metaTags();

        static::assertStringContainsString('robots', $html);
        static::assertStringContainsString('noindex', $html);
    }

    public function testRendersPageTitleAsOpenGraphAndTwitterTitle(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('My Page Title');
        $this->metaContext->setMeta($meta);

        $html = $this->extension->metaTags();

        static::assertStringContainsString('og:title', $html);
        static::assertStringContainsString('twitter:title', $html);
        static::assertStringContainsString('My Page Title', $html);
        static::assertStringNotContainsString('<title>', $html);
    }

    public function testReturnsEmptyStringWhenNoDescriptionOrRobots(): void
    {
        $meta = new Meta();
        $this->metaContext->setMeta($meta);

        static::assertSame('', $this->extension->metaTags());
    }

    public function testRendersDescriptionWhenTitleAlsoSet(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('My Title');
        $meta->setMetaDescription('My description');
        $this->metaContext->setMeta($meta);

        $html = $this->extension->metaTags();

        static::assertStringContainsString('name="description"', $html);
        static::assertStringContainsString('My description', $html);
    }

    public function testEmptyDescriptionIsNotRendered(): void
    {
        $meta = new Meta();
        $meta->setMetaDescription('');
        $this->metaContext->setMeta($meta);

        static::assertSame('', $this->extension->metaTags());
    }

    public function testExposesMetaTagsFunction(): void
    {
        $attributes = new ReflectionMethod(MetaExtension::class, 'metaTags')->getAttributes(AsTwigFunction::class);

        static::assertCount(1, $attributes);
        static::assertSame('meta_tags', $attributes[0]->newInstance()->name);
    }
}
