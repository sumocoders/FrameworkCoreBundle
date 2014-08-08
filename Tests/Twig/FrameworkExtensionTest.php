<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Twig;

use SumoCoders\FrameworkCoreBundle\Twig\FrameworkExtension;

class FrameworkExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FrameworkExtension
     */
    protected $frameworkExtension;

    /**
     * @inherit
     */
    protected function setUp()
    {
        $this->frameworkExtension = new FrameworkExtension(
            $this->getContainer()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContainer()
    {
        $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $container->method('getParameter')
            ->will(
                $this->returnValue(
                    array(
                        'existing' => null,
                    )
                )
            );

        return $container;
    }

    /**
     * @inherit
     */
    protected function tearDown()
    {
        $this->frameworkExtension = null;
    }

    /**
     * Test FrameworkExtension->getFunctions()
     */
    public function testGetFunctions()
    {
        $var = $this->frameworkExtension->getFunctions();
        $this->assertInternalType('array', $var);

        $containsBundleExists = false;
        foreach ($var as $function) {
            /** @var \Twig_SimpleFunction $function */
            if ($function->getName() == 'bundleExists') {
                $containsBundleExists = true;
            }
        }
        $this->assertTrue($containsBundleExists, 'bundleExists-function not found');
    }

    public function testBundleExists()
    {
        $this->assertTrue($this->frameworkExtension->bundleExists('existing'), 'existing bundle not found');
        $this->assertFalse($this->frameworkExtension->bundleExists('non-existing'), 'non-existing bundle found');
    }

    /**
     * Test FrameworkExtension->getName()
     */
    public function testGetName()
    {
        $this->assertEquals('framework_extension', $this->frameworkExtension->getName());
    }
}
