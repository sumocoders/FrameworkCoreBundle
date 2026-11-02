<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Service\MetaContext;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use Symfony\Contracts\Service\ResetInterface;

#[AllowMockObjectsWithoutExpectations]
final class MetaContextTest extends TestCase
{
    private PageTitle&MockObject $pageTitle;
    private MetaContext $context;

    protected function setUp(): void
    {
        $this->pageTitle = $this->createMock(PageTitle::class);
        $this->context = new MetaContext($this->pageTitle);
    }

    public function testGetMetaReturnsNullInitially(): void
    {
        static::assertNull($this->context->getMeta());
    }

    public function testSetAndGetMeta(): void
    {
        $meta = new Meta();

        $this->context->setMeta($meta);

        static::assertSame($meta, $this->context->getMeta());
    }

    public function testSetMetaNullClearsValue(): void
    {
        $this->context->setMeta(new Meta());

        $this->context->setMeta(null);

        static::assertNull($this->context->getMeta());
    }

    public function testPageTitleIsPushedToThePageTitleService(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('My Page Title');

        $this->pageTitle->expects(static::once())->method('setTitle')->with('My Page Title');

        $this->context->setMeta($meta);
    }

    public function testPageTitleServiceIsLeftUntouchedWhenNoPageTitleIsSet(): void
    {
        $meta = new Meta();
        $meta->setMetaDescription('My description');

        $this->pageTitle->expects(static::never())->method('setTitle');

        $this->context->setMeta($meta);
    }

    public function testEmptyPageTitleIsNotPushedToThePageTitleService(): void
    {
        $meta = new Meta();
        $meta->setPageTitle('');

        $this->pageTitle->expects(static::never())->method('setTitle');

        $this->context->setMeta($meta);
    }

    public function testSetMetaNullLeavesThePageTitleServiceUntouched(): void
    {
        $this->pageTitle->expects(static::never())->method('setTitle');

        $this->context->setMeta(null);
    }

    public function testResetClearsTheMeta(): void
    {
        $this->context->setMeta(new Meta());

        $this->context->reset();

        static::assertNull($this->context->getMeta());
    }

    public function testResetOnAnEmptyContextIsHarmless(): void
    {
        $this->context->reset();

        static::assertNull($this->context->getMeta());
    }

    /**
     * Implementing ResetInterface is what makes `->autoconfigure()` tag the service as
     * `kernel.reset`, which is what stops a worker leaking meta between requests.
     */
    public function testIsResettableSoAutoconfigurationTagsItForKernelReset(): void
    {
        static::assertInstanceOf(ResetInterface::class, $this->context);
    }
}
