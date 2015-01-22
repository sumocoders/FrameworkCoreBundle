<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\EventListener;

use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;

class DefaultMenuListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultMenuListener
     */
    protected $defaultMenuListener;

    /**
     * @inherit
     */
    protected function setUp()
    {
        $this->defaultMenuListener = new DefaultMenuListener();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityContext()
    {
        $securityContext = $this->getMock(
            '\Symfony\Component\Security\Core\SecurityContext',
            array(),
            array(),
            '',
            false
        );

        return $securityContext;
    }

    /**
     * @inherit
     */
    protected function tearDown()
    {
        $this->defaultMenuListener = null;
    }

    /**
     * Test the getters and setters
     */
    public function testGettersAndSetters()
    {
        $securityContext = $this->getSecurityContext();
        $this->defaultMenuListener->setSecurityContext($securityContext);
        $this->assertEquals($securityContext, $this->defaultMenuListener->getSecurityContext());
    }
}
