<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Attribute\HasMeta;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\EventListener\MetaContextListener;
use SumoCoders\FrameworkCoreBundle\Service\MetaContext;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MetaContextListenerTest extends TestCase
{
    private MetaContext $metaContext;
    private MetaContextListener $listener;

    protected function setUp(): void
    {
        $this->metaContext = new MetaContext($this->createStub(PageTitle::class));
        $this->listener = new MetaContextListener($this->metaContext);
    }

    public function testNoArgumentsLeavesContextUnchanged(): void
    {
        $event = $this->createEventWithArguments([]);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testNonObjectArgumentIsSkipped(): void
    {
        $event = $this->createEventWithArguments(['scalar_value']);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testObjectWithoutHasMetaAttributeIsSkipped(): void
    {
        $event = $this->createEventWithArguments([new EntityWithoutMeta()]);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testObjectWithHasMetaSetsMetaOnContext(): void
    {
        $meta = new Meta();
        $entity = new EntityWithMeta($meta);
        $event = $this->createEventWithArguments([$entity], ['a0' => 'my-slug']);

        $this->listener->onKernelControllerArguments($event);

        static::assertSame($meta, $this->metaContext->getMeta());
    }

    public function testObjectWithCustomMethodCallsCorrectMethod(): void
    {
        $meta = new Meta();
        $entity = new EntityWithCustomMethod($meta);
        $event = $this->createEventWithArguments([$entity], ['a0' => 'my-slug']);

        $this->listener->onKernelControllerArguments($event);

        static::assertSame($meta, $this->metaContext->getMeta());
    }

    public function testMethodReturningNullDoesNotSetContext(): void
    {
        $entity = new EntityWithNullMeta();
        $event = $this->createEventWithArguments([$entity]);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testMethodReturningNonMetaObjectDoesNotSetContext(): void
    {
        $entity = new EntityWithWrongType();
        $event = $this->createEventWithArguments([$entity]);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testStopsAfterFirstHasMetaMatch(): void
    {
        $firstMeta = new Meta();
        $secondMeta = new Meta();
        $event = $this->createEventWithArguments(
            [new EntityWithMeta($firstMeta), new EntityWithMeta($secondMeta)],
            ['a0' => 'my-slug', 'a1' => 'my-slug'],
        );

        $this->listener->onKernelControllerArguments($event);

        static::assertSame($firstMeta, $this->metaContext->getMeta());
    }

    public function testSkipsNonMatchingArgumentsBeforeMatch(): void
    {
        $meta = new Meta();
        $event = $this->createEventWithArguments(
            [new EntityWithoutMeta(), new EntityWithMeta($meta)],
            ['a1' => 'my-slug'],
        );

        $this->listener->onKernelControllerArguments($event);

        static::assertSame($meta, $this->metaContext->getMeta());
    }

    public function testRouteParameterNotMatchingTheSlugDoesNotSetContext(): void
    {
        $entity = new EntityWithMeta(new Meta());
        $event = $this->createEventWithArguments([$entity], ['a0' => '42']);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testInternalRouteIsSkipped(): void
    {
        $event = $this->createEventWithArguments([new EntityWithMeta(new Meta())], [
            '_route' => '_profiler',
            'a0' => 'my-slug',
        ]);

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    public function testSubRequestIsSkipped(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'some_route');
        $request->attributes->set('a0', 'my-slug');

        $event = new ControllerArgumentsEvent(
            $this->createStub(HttpKernelInterface::class),
            // @mago-expect analysis:unused-parameter
            static function (object $a0): void {},
            [new EntityWithMeta(new Meta())],
            $request,
            HttpKernelInterface::SUB_REQUEST,
        );

        $this->listener->onKernelControllerArguments($event);

        static::assertNull($this->metaContext->getMeta());
    }

    /** @param array<string, string> $attributes */
    // @mago-expect analysis:imprecise-type,unused-parameter,missing-return-type,missing-parameter-type
    private function createEventWithArguments(array $arguments, array $attributes = []): ControllerArgumentsEvent
    {
        // ControllerArgumentsEvent is final so it cannot be mocked.
        // getNamedArguments() maps callable parameter names to argument values;
        // the listener only iterates values so generic parameter names work fine.
        $controllers = [
            static function () {},
            static function ($a0) {},
            static function ($a0, $a1) {},
            static function ($a0, $a1, $a2) {},
        ];

        $request = Request::create('/');
        $request->attributes->set('_route', 'some_route');
        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return new ControllerArgumentsEvent(
            $this->createStub(HttpKernelInterface::class),
            $controllers[count($arguments)],
            $arguments,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}

#[HasMeta]
class EntityWithMeta
{
    public function __construct(
        private readonly Meta $meta,
        private readonly string $slug = 'my-slug',
    ) {
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}

#[HasMeta(meta: 'fetchMeta', slug: 'fetchSlug')]
class EntityWithCustomMethod
{
    public function __construct(
        private readonly Meta $meta,
        private readonly string $slug = 'my-slug',
    ) {
    }

    public function fetchMeta(): Meta
    {
        return $this->meta;
    }

    public function fetchSlug(): string
    {
        return $this->slug;
    }
}

#[HasMeta]
class EntityWithNullMeta
{
    public function getMeta(): ?Meta
    {
        return null;
    }

    public function getSlug(): string
    {
        return 'my-slug';
    }
}

#[HasMeta]
class EntityWithWrongType
{
    public function getMeta(): object
    {
        return new \stdClass();
    }

    public function getSlug(): string
    {
        return 'my-slug';
    }
}

class EntityWithoutMeta
{
}
