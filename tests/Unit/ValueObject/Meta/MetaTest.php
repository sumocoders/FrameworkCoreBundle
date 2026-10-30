<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\ValueObject\Meta;

use Melbahja\Seo\MetaTags;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;

final class MetaTest extends TestCase
{
    public function testGetPageTitleReturnsNullByDefault(): void
    {
        $meta = new Meta();

        static::assertNull($meta->getPageTitle());
    }

    public function testSetAndGetPageTitle(): void
    {
        $meta = new Meta();

        $meta->setPageTitle('My Title');

        static::assertSame('My Title', $meta->getPageTitle());
    }

    public function testSetPageTitleNull(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('Original');

        $meta->setPageTitle(null);

        static::assertNull($meta->getPageTitle());
    }

    public function testSetPageTitleReturnsSelf(): void
    {
        $meta = new Meta();

        static::assertSame($meta, $meta->setPageTitle('Title'));
    }

    public function testGetMetaDescriptionReturnsNullByDefault(): void
    {
        $meta = new Meta();

        static::assertNull($meta->getMetaDescription());
    }

    public function testSetAndGetMetaDescription(): void
    {
        $meta = new Meta();

        $meta->setMetaDescription('My description');

        static::assertSame('My description', $meta->getMetaDescription());
    }

    public function testSetMetaDescriptionNull(): void
    {
        $meta = new Meta();
        $meta->setMetaDescription('Original');

        $meta->setMetaDescription(null);

        static::assertNull($meta->getMetaDescription());
    }

    public function testSetMetaDescriptionReturnsSelf(): void
    {
        $meta = new Meta();

        static::assertSame($meta, $meta->setMetaDescription('Description'));
    }

    public function testGetRobotsReturnsEmptyArrayByDefault(): void
    {
        $meta = new Meta();

        static::assertSame([], $meta->getRobots());
    }

    public function testSetAndGetRobotsSingle(): void
    {
        $meta = new Meta();

        $meta->setRobots([RobotsDirective::NoIndex]);

        static::assertSame([RobotsDirective::NoIndex], $meta->getRobots());
    }

    public function testSetAndGetRobotsMultiple(): void
    {
        $meta = new Meta();

        $meta->setRobots([RobotsDirective::NoIndex, RobotsDirective::NoFollow]);

        static::assertSame([RobotsDirective::NoIndex, RobotsDirective::NoFollow], $meta->getRobots());
    }

    public function testSetRobotsEmptyArrayStoresNull(): void
    {
        $meta = new Meta();
        $meta->setRobots([RobotsDirective::NoIndex]);

        $meta->setRobots([]);

        static::assertSame([], $meta->getRobots());
    }

    public function testSetRobotsReturnsSelf(): void
    {
        $meta = new Meta();

        static::assertSame($meta, $meta->setRobots([]));
    }

    public function testGetMetaTagsInstanceReturnsMetaTagsObject(): void
    {
        $meta = new Meta();

        static::assertInstanceOf(MetaTags::class, $meta->getMetaTagsInstance());
    }

    public function testGetMetaTagsInstanceIsCachedOnSubsequentCalls(): void
    {
        $meta = new Meta();

        static::assertSame($meta->getMetaTagsInstance(), $meta->getMetaTagsInstance());
    }

    public function testGetMetaTagsInstanceWithNothingSetReturnsEmpty(): void
    {
        $meta = new Meta();

        static::assertSame('', $meta->getMetaTagsInstance()->__toString());
    }

    public function testGetMetaTagsInstanceWithPageTitleRendersTitle(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('My Page Title');

        $html = $meta->getMetaTagsInstance()->__toString();

        static::assertStringContainsString('My Page Title', $html);
    }

    public function testGetMetaTagsInstanceWithEmptyPageTitleDoesNotRender(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('');

        static::assertSame('', $meta->getMetaTagsInstance()->__toString());
    }

    public function testGetMetaTagsInstanceWithMetaDescriptionRendersTag(): void
    {
        $meta = new Meta();
        $meta->setMetaDescription('My description');

        $html = $meta->getMetaTagsInstance()->__toString();

        static::assertStringContainsString('name="description"', $html);
        static::assertStringContainsString('My description', $html);
    }

    public function testGetMetaTagsInstanceWithEmptyMetaDescriptionDoesNotRender(): void
    {
        $meta = new Meta();
        $meta->setMetaDescription('');

        static::assertSame('', $meta->getMetaTagsInstance()->__toString());
    }

    public function testGetMetaTagsInstanceWithRobotsRendersTag(): void
    {
        $meta = new Meta();
        $meta->setRobots([RobotsDirective::NoIndex]);

        $html = $meta->getMetaTagsInstance()->__toString();

        static::assertStringContainsString('robots', $html);
        static::assertStringContainsString('noindex', $html);
    }
}
