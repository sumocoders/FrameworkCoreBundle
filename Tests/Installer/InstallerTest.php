<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Composer;

use SumoCoders\FrameworkCoreBundle\Installer\Installer;

class InstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Installer
     */
    protected $installer;

    public function setUp()
    {
        $this->installer = new Installer();
    }

    public function testDecoratedMessage()
    {
        $this->assertEquals(
            '<error>I will be wrapped in an errortag.</error>',
            $this->installer->getDecoratedMessage(
                'I will be wrapped in an errortag.',
                'error',
                true
            )
        );
    }

    public function testNotDecoratedMessage()
    {
        $this->assertEquals(
            'I wont be wrapped in an errortag.',
            $this->installer->getDecoratedMessage(
                'I wont be wrapped in an errortag.',
                'error',
                false
            )
        );
    }

    public function testLocalProjectInformation()
    {
        $information = $this->installer->extractInformationFromPath(
            '/Users/tijs/Sites/foo/bar'
        );

        $this->assertTrue($information['is_local']);
    }

    public function testNotLocalProjectInformation()
    {
        $information = $this->installer->extractInformationFromPath(
            '/home/sumocoders/apps/foo/bar/releases/20150415160001'
        );

        $this->assertFalse($information['is_local']);
    }

    public function testCorrectProjectInformation()
    {
        $information = $this->installer->extractInformationFromPath(
            '/Users/tijs/Sites/foo/bar'
        );

        $this->assertEquals('foo', $information['client']);
        $this->assertEquals('bar', $information['project']);
    }

    public function testNotCorrectProjectInformation()
    {
        $information = $this->installer->extractInformationFromPath(
            '/home/sumocoders/apps/foo/bar/releases/20150415160001'
        );

        $this->assertNull($information['client']);
        $this->assertNull($information['project']);
    }

    public function testValidUpdateCapfile()
    {
        $originalContent = <<<ORIGINAL
    set :first_name, 'foo'
    set :last_name,  'bar'
ORIGINAL;

        $expectedContent = <<<EXPECTED
    set :first_name, 'John'
    set :last_name,  'Doe'
EXPECTED;

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, $originalContent);

        $this->installer->updateCapfile(
            $tempFile,
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]
        );

        $this->assertEquals(
            $expectedContent,
            file_get_contents($tempFile)
        );

        unlink($tempFile);
    }

    public function testValidUpdateYmlFile()
    {
        $originalContent = <<<ORIGINAL
parameters:
    first_name: foo
    last_name:  bar
ORIGINAL;

        $expectedContent = <<<EXPECTED
parameters:
    first_name: John
    last_name: Doe

EXPECTED;

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, $originalContent);

        $this->installer->updateYmlFile(
            $tempFile,
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]
        );

        $this->assertEquals(
            ltrim($expectedContent),
            file_get_contents($tempFile)
        );

        unlink($tempFile);
    }
}
