<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\BreadCrumb;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\BreadCrumb\BreadCrumbBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BreadCrumbBuilderTest extends TestCase
{
    /**
     * @var BreadCrumbBuilder
     */
    protected $breadCrumbBuilder;

    /**
     * @inherit
     */
    protected function tearDown()
    {
        $this->breadCrumbBuilder = null;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventDispatcher()
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @return MockObject
     */
    protected function getFactory($item)
    {
        /** @var MockBuilder $factory */
        $factory = $this->createMock(FactoryInterface::class);
        $factory->method('createItem')
            ->will(
                $this->returnValue(
                    $item
                )
            );

        return $factory;
    }

    protected function createSimpleBreadCrumb()
    {
        $item = new MenuItem(
            'root',
            $this->createMock(FactoryInterface::class)
        );
        $factory = $this->getFactory($item);

        $this->breadCrumbBuilder = new BreadCrumbBuilder(
            $factory,
            $this->getEventDispatcher()
        );
    }

    public function testCreateBreadCrumbWithEmptyRequestAndEmptyMenu()
    {
        $this->createSimpleBreadCrumb();

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(
                $this->createMock(Request::class)
            );

        $breadCrumb = $this->breadCrumbBuilder->createBreadCrumb($requestStack);

        $this->assertTrue($breadCrumb->hasChildren());
        $this->assertEquals(1, count($breadCrumb->getChildren()));
    }

    public function testIfLastItemDoesNotHaveAnUri()
    {
        $this->createSimpleBreadCrumb();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(
                $this->createMock(Request::class)
            );

        $breadCrumb = $this->breadCrumbBuilder->createBreadCrumb($requestStack);

        $lastChild = $breadCrumb->getLastChild();
        $this->assertNull($lastChild->getUri());
    }

    public function testIfBreadCrumbIsEmptyWhenDontExtraFromTheRequestIsEnabled()
    {
        $this->createSimpleBreadCrumb();
        $this->breadCrumbBuilder->dontExtractFromTheRequest();

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(
                $this->createMock(Request::class)
            );

        $breadCrumb = $this->breadCrumbBuilder->createBreadCrumb($requestStack);

        $this->assertFalse($breadCrumb->hasChildren());
    }

    public function testIfSimpleItemIsAdded()
    {
        $this->createSimpleBreadCrumb();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(
                $this->createMock(Request::class)
            );


        $this->breadCrumbBuilder->dontExtractFromTheRequest();
        $this->breadCrumbBuilder->addSimpleItem('first', 'http://www.example.org');
        $this->breadCrumbBuilder->addSimpleItem('last', 'http://www.example.org');

        $breadCrumb = $this->breadCrumbBuilder->createBreadCrumb($requestStack);

        $this->assertEquals(2, count($breadCrumb->getChildren()));

        $this->assertEquals('first', $breadCrumb->getChild('first')->getLabel());
        $this->assertEquals('http://www.example.org', $breadCrumb->getChild('first')->getUri());
        $this->assertEquals('last', $breadCrumb->getLastChild()->getLabel());
        $this->assertNull($breadCrumb->getLastChild()->getUri());
    }

    public function testIfBreadCrumbHasOnlyHomeWhenItemsIsSetWithEmptyArray()
    {
        $this->createSimpleBreadCrumb();

        $this->breadCrumbBuilder->addSimpleItem('first', 'http://www.example.org');
        $this->breadCrumbBuilder->overwriteItems([]);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(
                $this->createMock(Request::class)
            );

        $breadCrumb = $this->breadCrumbBuilder->createBreadCrumb($requestStack);

        $this->assertEquals(1, count($breadCrumb->getChildren()));
    }

    public function testIfItemIsAdded()
    {
        $this->createSimpleBreadCrumb();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(
                $this->createMock(Request::class)
            );


        $first = new MenuItem('first', $this->getFactory(null));
        $first->setUri('http://www.example.org');
        $this->breadCrumbBuilder->addItem($first);

        $last = new MenuItem('last', $this->getFactory(null));
        $this->breadCrumbBuilder->addItem($last);

        $this->breadCrumbBuilder->dontExtractFromTheRequest();
        $breadCrumb = $this->breadCrumbBuilder->createBreadCrumb($requestStack);

        $this->assertEquals(2, count($breadCrumb->getChildren()));

        $this->assertEquals('first', $breadCrumb->getChild('first')->getLabel());
        $this->assertEquals('http://www.example.org', $breadCrumb->getChild('first')->getUri());
        $this->assertEquals('last', $breadCrumb->getLastChild()->getLabel());
        $this->assertNull($breadCrumb->getLastChild()->getUri());
    }
}
