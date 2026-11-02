<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\DataTransferObject\Meta;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\DataTransferObject\SeoDataTransferObject;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;

final class SeoDataTransferObjectTest extends TestCase
{
    public function testFromMetaWithNothingSet(): void
    {
        $dto = SeoDataTransferObject::fromMeta(new Meta());

        static::assertFalse($dto->overridePageTitle);
        static::assertNull($dto->pageTitle);
        static::assertFalse($dto->overrideMetaDescription);
        static::assertNull($dto->metaDescription);
        static::assertNull($dto->robotsIndex);
        static::assertNull($dto->robotsFollow);
        static::assertSame([], $dto->robotsAdditional);
    }

    public function testFromMetaWithPageTitleAndMetaDescriptionSet(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('My title');
        $meta->setMetaDescription('My description');

        $dto = SeoDataTransferObject::fromMeta($meta);

        static::assertTrue($dto->overridePageTitle);
        static::assertSame('My title', $dto->pageTitle);
        static::assertTrue($dto->overrideMetaDescription);
        static::assertSame('My description', $dto->metaDescription);
    }

    public function testFromMetaSplitsRobotsIntoIndexFollowAndAdditional(): void
    {
        $meta = new Meta();
        $meta->setRobots([RobotsDirective::NoIndex, RobotsDirective::Follow, RobotsDirective::NoArchive]);

        $dto = SeoDataTransferObject::fromMeta($meta);

        static::assertSame(RobotsDirective::NoIndex, $dto->robotsIndex);
        static::assertSame(RobotsDirective::Follow, $dto->robotsFollow);
        static::assertSame([RobotsDirective::NoArchive], $dto->robotsAdditional);
    }

    public function testFromMetaWithOnlyAdditionalRobotsLeavesIndexAndFollowNull(): void
    {
        $meta = new Meta();
        $meta->setRobots([RobotsDirective::NoSnippet]);

        $dto = SeoDataTransferObject::fromMeta($meta);

        static::assertNull($dto->robotsIndex);
        static::assertNull($dto->robotsFollow);
        static::assertSame([RobotsDirective::NoSnippet], $dto->robotsAdditional);
    }

    public function testApplyToStoresNullWhenOverrideIsFalse(): void
    {
        $dto = new SeoDataTransferObject(
            overridePageTitle: false,
            pageTitle: 'Ignored title',
            overrideMetaDescription: false,
            metaDescription: 'Ignored description',
        );
        $meta = new Meta();
        $meta->setPageTitle('Existing title');
        $meta->setMetaDescription('Existing description');

        $dto->applyTo($meta);

        static::assertNull($meta->getPageTitle());
        static::assertNull($meta->getMetaDescription());
    }

    public function testApplyToStoresValueWhenOverrideIsTrue(): void
    {
        $dto = new SeoDataTransferObject(
            overridePageTitle: true,
            pageTitle: 'New title',
            overrideMetaDescription: true,
            metaDescription: 'New description',
        );
        $meta = new Meta();

        $dto->applyTo($meta);

        static::assertSame('New title', $meta->getPageTitle());
        static::assertSame('New description', $meta->getMetaDescription());
    }

    public function testApplyToWithNoRobotsSelectedStoresEmptyArray(): void
    {
        $dto = new SeoDataTransferObject();
        $meta = new Meta();
        $meta->setRobots([RobotsDirective::NoIndex]);

        $dto->applyTo($meta);

        static::assertSame([], $meta->getRobots());
    }

    public function testApplyToCombinesIndexFollowAndAdditionalRobots(): void
    {
        $dto = new SeoDataTransferObject(
            robotsIndex: RobotsDirective::NoIndex,
            robotsFollow: RobotsDirective::Follow,
            robotsAdditional: [RobotsDirective::NoSnippet, RobotsDirective::NoImageIndex],
        );
        $meta = new Meta();

        $dto->applyTo($meta);

        static::assertSame(
            [
                RobotsDirective::NoIndex,
                RobotsDirective::Follow,
                RobotsDirective::NoSnippet,
                RobotsDirective::NoImageIndex,
            ],
            $meta->getRobots(),
        );
    }
}
