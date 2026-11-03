<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\DependencyInjection\SumoCodersFrameworkCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SumoCodersFrameworkCoreExtensionTest extends TestCase
{
    public function testPrependsAuditTrailChannelWhenMonologIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension($this->createMonologExtension());

        new SumoCodersFrameworkCoreExtension()->prepend($container);

        $this->assertSame([['channels' => ['audit_trail']]], $container->getExtensionConfig('monolog'));
    }

    public function testDoesNothingWithoutMonolog(): void
    {
        $container = new ContainerBuilder();

        new SumoCodersFrameworkCoreExtension()->prepend($container);

        $this->assertSame([], $container->getExtensionConfig('monolog'));
    }

    private function createMonologExtension(): Extension
    {
        return new class extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'monolog';
            }
        };
    }
}
