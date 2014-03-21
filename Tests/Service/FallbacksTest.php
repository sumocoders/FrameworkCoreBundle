<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Service;

require_once __DIR__ . '/../../Service/Fallbacks.php';

use SumoCoders\FrameworkCoreBundle\Service\Fallbacks;

class FallbacksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Fallbacks
     */
    private $fallbacks;

    /**
     * @var array
     */
    private $defaultData = array(
        'foo' => 'bar',
        'name' => array(
            'first' => 'John',
            'last' => 'Doe',
        ),
        'servers' => array(
            'web01',
            'web02',
        ),
        'a' => array(
            'very' => array(
                'deep' => array(
                    'array' => array(
                        'x' => 0,
                        'y' => 0,
                    )
                )
            ),
        ),
        'errorcodes' => array(
            404 => 'Not Found',
            500 => 'Internal server error',
        ),
    );

    /**
     * Test Fallbacks()
     */
    public function testPopulateFallbacks()
    {
        $this->fallbacks = new Fallbacks();
        $this->assertNull($this->fallbacks->get('foo')); // we no data is passed we can't grab anything
    }

    /**
     * Tests Fallbacks->get()
     */
    public function testGet()
    {
        $this->fallbacks = new Fallbacks($this->defaultData);

        $this->assertEquals($this->defaultData['foo'], $this->fallbacks->get('foo'));
        $this->assertEquals($this->defaultData['name']['first'], $this->fallbacks->get('name.first'));
        $this->assertNull($this->fallbacks->get('name')); // we can't grab a key-value-array
        $this->assertEquals($this->defaultData['servers'], $this->fallbacks->get('servers'));
        $this->assertEquals(
            $this->defaultData['a']['very']['deep']['array']['x'],
            $this->fallbacks->get('a.very.deep.array.x')
        );
        $this->assertEquals($this->defaultData['errorcodes'][404], $this->fallbacks->get('errorcodes.404'));
    }
}
