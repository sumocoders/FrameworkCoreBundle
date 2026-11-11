<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Message\CreateMcpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Message\RevokeMcpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\MessageHandler\CreateMcpTokenHandler;
use SumoCoders\FrameworkCoreBundle\Mcp\MessageHandler\RevokeMcpTokenHandler;
use SumoCoders\FrameworkCoreBundle\Mcp\Repository\McpTokenRepository;
use Symfony\Component\Security\Core\User\InMemoryUser;

class McpTokenHandlerTest extends TestCase
{
    public function testCreateStoresOnlyTheHashAndReturnsPrefixedPlainToken(): void
    {
        $user = new InMemoryUser('jane.doe', null);
        $message = new CreateMcpToken($user);
        $message->name = 'Claude Desktop laptop';

        $stored = null;
        $repository = $this->createMock(McpTokenRepository::class);
        $repository
            ->expects($this->once())
            ->method('add')
            ->willReturnCallback(static function (#[\SensitiveParameter] McpToken $token) use (&$stored): void {
                $stored = $token;
            });

        $plainToken = new CreateMcpTokenHandler($repository, 'automator_mcp_')($message);

        static::assertMatchesRegularExpression('/^automator_mcp_[0-9a-f]{64}$/', $plainToken);
        static::assertInstanceOf(McpToken::class, $stored);
        static::assertSame($user, $stored->getUser());
        static::assertSame('Claude Desktop laptop', $stored->getName());
        static::assertNull($stored->getLastUsedAt());

        // The hash must match, so findOneByPlainToken() finds the token again.
        static::assertSame(McpToken::hash($plainToken), $stored->getTokenHash());
    }

    public function testRevokeRemovesToken(): void
    {
        $token = new McpToken(new InMemoryUser('jane.doe', null), 'Laptop', 'hash', new \DateTimeImmutable());

        $repository = $this->createMock(McpTokenRepository::class);
        $repository->method('find')->with(5)->willReturn($token);
        $repository->expects($this->once())->method('remove')->with($token);

        new RevokeMcpTokenHandler($repository)(new RevokeMcpToken(5));
    }

    public function testRevokeUnknownTokenThrows(): void
    {
        $repository = $this->createMock(McpTokenRepository::class);
        $repository->method('find')->willReturn(null);
        $repository->expects($this->never())->method('remove');

        $this->expectException(\InvalidArgumentException::class);

        new RevokeMcpTokenHandler($repository)(new RevokeMcpToken(5));
    }
}
