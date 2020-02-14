<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DefaultMenuListenerTest extends TestCase
{
    /**
     * @var DefaultMenuListener
     */
    protected $defaultMenuListener;

    /**
     * @inherit
     */
    protected function setUp(): void
    {
        $this->defaultMenuListener = new DefaultMenuListener(
            $this->getSecurityAuthorizationChecker(),
            $this->getSecurityTokenStorage()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityAuthorizationChecker()
    {
        $container = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();

        return $container;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityTokenStorage()
    {
        $securityContext = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        return $securityContext;
    }

    /**
     * @inherit
     */
    protected function tearDown(): void
    {
        $this->defaultMenuListener = null;
    }

    /**
     * Test the getters and setters
     */
    public function testGettersAndSetters()
    {
        $securityAuthorizationChecker = $this->getSecurityAuthorizationChecker();
        $this->assertEquals(
            $securityAuthorizationChecker,
            $this->defaultMenuListener->getSecurityAuthorizationChecker()
        );

        $securityTokenStorage = $this->getSecurityTokenStorage();
        $this->assertEquals($securityTokenStorage, $this->defaultMenuListener->getSecurityTokenStorage());
    }
}
