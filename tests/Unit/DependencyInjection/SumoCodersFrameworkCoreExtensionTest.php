<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\DependencyInjection\SumoCodersFrameworkCoreExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function testLeavesMcpTokenUnmappedWithoutUserClass(): void
    {
        $orm = $this->processWithDoctrine([]);

        static::assertArrayNotHasKey('resolve_target_entities', $orm);
        static::assertArrayNotHasKey('FrameworkCoreBundleMcp', $orm['mappings']);
    }

    public function testMapsMcpTokenToConfiguredUserClass(): void
    {
        $orm = $this->processWithDoctrine(['mcp_tokens' => ['user_class' => InMemoryUser::class]]);

        static::assertSame(
            [UserInterface::class => InMemoryUser::class],
            $orm['resolve_target_entities'] ?? null,
        );
        static::assertSame(
            'SumoCoders\FrameworkCoreBundle\Mcp\Entity',
            $orm['mappings']['FrameworkCoreBundleMcp']['prefix'],
        );
    }

    public function testRejectsUserClassThatIsNoSecurityUser(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processWithDoctrine(['mcp_tokens' => ['user_class' => \stdClass::class]]);
    }

    public function testSetsTokenPrefixParameter(): void
    {
        $container = new ContainerBuilder();
        new SumoCodersFrameworkCoreExtension()->load([['mcp_tokens' => ['token_prefix' => 'app_mcp_']]], $container);

        static::assertSame(
            'app_mcp_',
            $container->getParameter('sumo_coders_framework_core.mcp_tokens.token_prefix'),
        );
    }

    /**
     * @param array<string, mixed> $bundleConfig
     *
     * @return array{
     *     mappings: array<string, array<string, string>>,
     *     resolve_target_entities?: array<string, string>
     * } the merged doctrine.orm config
     */
    private function processWithDoctrine(array $bundleConfig): array
    {
        $doctrine = new class extends Extension {
            /** @var array<array<mixed>> */
            public array $configs = [];

            public function load(array $configs, ContainerBuilder $container): void
            {
                $this->configs = $configs;
            }

            public function getAlias(): string
            {
                return 'doctrine';
            }
        };
        $container = new ContainerBuilder();
        $container->registerExtension(new SumoCodersFrameworkCoreExtension());
        $container->registerExtension($doctrine);
        $container->loadFromExtension('sumo_coders_framework_core', $bundleConfig);

        new MergeExtensionConfigurationPass()->process($container);

        /** @var list<array{orm?: array<string, array<string, mixed>>}> $configs */
        $configs = $doctrine->configs;

        // Merge the prepended configs the way the DoctrineBundle config tree would for these keys.
        $orm = ['mappings' => []];
        foreach ($configs as $config) {
            $orm = array_replace_recursive($orm, $config['orm'] ?? []);
        }

        /**
         * @var array{
         *     mappings: array<string, array<string, string>>,
         *     resolve_target_entities?: array<string, string>
         * }
         */
        return $orm;
    }
}
