<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Security\McpTokenVoter;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

class McpTokenVoterTest extends TestCase
{
    public function testOwnerMayRevokeOwnTokenWithoutAdminCheck(): void
    {
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager->expects($this->never())->method('decide');

        $vote = new McpTokenVoter($accessDecisionManager)->vote(
            $this->createSecurityToken('jane.doe'),
            $this->createMcpToken('jane.doe'),
            [McpTokenVoter::REVOKE],
        );

        static::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testAdminMayRevokeTokenOfOtherUser(): void
    {
        $securityToken = $this->createSecurityToken('admin');

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($securityToken, ['ROLE_ADMIN'])
            ->willReturn(true);

        $vote = new McpTokenVoter($accessDecisionManager)->vote(
            $securityToken,
            $this->createMcpToken('jane.doe'),
            [McpTokenVoter::REVOKE],
        );

        static::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testNonAdminMayNotRevokeTokenOfOtherUser(): void
    {
        $accessDecisionManager = $this->createStub(AccessDecisionManagerInterface::class);
        $accessDecisionManager->method('decide')->willReturn(false);

        $vote = new McpTokenVoter($accessDecisionManager)->vote(
            $this->createSecurityToken('john.doe'),
            $this->createMcpToken('jane.doe'),
            [McpTokenVoter::REVOKE],
        );

        static::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testAnonymousMayNotRevoke(): void
    {
        $vote = new McpTokenVoter($this->createStub(AccessDecisionManagerInterface::class))->vote(
            new NullToken(),
            $this->createMcpToken('jane.doe'),
            [McpTokenVoter::REVOKE],
        );

        static::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testAbstainsOnOtherAttributesAndSubjects(): void
    {
        $voter = new McpTokenVoter($this->createStub(AccessDecisionManagerInterface::class));
        $securityToken = $this->createSecurityToken('jane.doe');

        static::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($securityToken, $this->createMcpToken('jane.doe'), ['EDIT']),
        );
        static::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($securityToken, new \stdClass(), [McpTokenVoter::REVOKE]),
        );
    }

    private function createSecurityToken(string $identifier): UsernamePasswordToken
    {
        $user = new InMemoryUser($identifier, null, ['ROLE_USER']);

        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    private function createMcpToken(string $ownerIdentifier): McpToken
    {
        return new McpToken(
            new InMemoryUser($ownerIdentifier, null),
            'Laptop',
            McpToken::hash('mcp_secret'),
            new \DateTimeImmutable(),
        );
    }
}
