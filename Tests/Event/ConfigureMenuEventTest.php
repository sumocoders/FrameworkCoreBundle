<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;

class ConfigureMenuEventTest extends TestCase
{
    /**
     * @var ConfigureMenuEvent
     */
    protected $configureMenuEvent;

    /**
     * @inherit
     */
    protected function setUp()
    {
        $this->configureMenuEvent = new ConfigureMenuEvent(
            $this->getFactory(),
            $this->getItem()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFactory()
    {
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        return $factory;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getItem()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();

        return $item;
    }

    /**
     * @inherit
     */
    protected function tearDown()
    {
        $this->configureMenuEvent = null;
    }

    /**
     * Test FrameworkExtension->getName()
     */
    public function testName()
    {
        $this->assertEquals('framework_core.configure_menu', ConfigureMenuEvent::EVENT_NAME);
    }

    /**
     * Test the getters and setters
     */
    public function testGettersAndSetters()
    {
        $this->assertEquals($this->getFactory(), $this->configureMenuEvent->getFactory());
        $this->assertEquals($this->getItem(), $this->configureMenuEvent->getMenu());
    }
}
