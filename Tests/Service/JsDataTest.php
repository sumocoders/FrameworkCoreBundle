<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Service;

use SumoCoders\FrameworkCoreBundle\Service\JsData;

class JsDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsData
     */
    private $jsData;

    /**
     * @inherit
     */
    protected function setUp()
    {
        $this->jsData = new JsData();
    }

    /**
     * @inherit
     */
    protected function tearDown()
    {
        $this->jsData = null;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequestStack()
    {
        $currentRequest = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $currentRequest->method('getLocale')
            ->will(
                $this->returnValue('nl')
            );

        $requestStack = $this->getMock('\Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->method('getCurrentRequest')
            ->will(
                $this->returnValue(
                    $currentRequest
                )
            );

        return $requestStack;
    }

    /**
     * Test jsData->get()
     */
    public function testGet()
    {
        $this->jsData->setRequestStack($this->getRequestStack());
        $this->assertEquals('nl', $this->jsData->get('request.locale'));
    }

    /**
     * Test jsData->parse()
     */
    public function testParse()
    {
        $data = array();
        $var = $this->jsData->parse();
        $this->assertEquals(json_encode($data), $var);

        $data = new \stdClass();
        $data->{'request.locale'} = 'nl';
        $this->jsData->setRequestStack($this->getRequestStack());
        $var = $this->jsData->parse();
        $this->assertEquals(json_encode($data), $var);
    }
}
