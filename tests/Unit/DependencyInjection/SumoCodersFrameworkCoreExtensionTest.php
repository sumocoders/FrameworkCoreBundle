<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\DependencyInjection\SumoCodersFrameworkCoreExtension;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SumoCodersFrameworkCoreExtensionTest extends TestCase
{
    public function testRegistersAuditTrailChannelWhenMonologIsRegistered(): void
    {
        $monolog = new class extends Extension {
            /** @var array<array<mixed>> */
            public array $configs = [];

            public function load(array $configs, ContainerBuilder $container): void
            {
                $this->configs = $configs;
            }

            public function getAlias(): string
            {
                return 'monolog';
            }
        };
        $container = new ContainerBuilder();
        $container->registerExtension(new SumoCodersFrameworkCoreExtension());
        $container->registerExtension($monolog);

        // The pass only calls prepend() on extensions implementing PrependExtensionInterface, like the kernel does
        new MergeExtensionConfigurationPass()->process($container);

        $this->assertSame([['channels' => ['audit_trail']]], $monolog->configs);
    }

    public function testDoesNothingWithoutMonolog(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SumoCodersFrameworkCoreExtension());

        new MergeExtensionConfigurationPass()->process($container);

        $this->assertSame([], $container->getExtensionConfig('monolog'));
    }
}
