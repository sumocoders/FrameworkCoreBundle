<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Composer;

use Composer\IO\ConsoleIO;
use SumoCoders\FrameworkCoreBundle\Composer\ScriptHandler;

class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test ScriptHandler->runCommandOnlyInDevMode()
     */
    public function testRunCommandOnlyInDevMode()
    {
        $io = $this->getMockBuilder(ConsoleIO::class)
            ->disableOriginalConstructor()
            ->getMock();
        $io->method('isDecorated')
            ->willReturn(true);

        $this->expectOutputString('foo' . "\n");
        ScriptHandler::runCommandOnlyInDevMode('echo "foo"', $io, true);
    }
}
