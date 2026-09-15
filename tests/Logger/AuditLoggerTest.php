<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditLoggerTest extends TestCase
{
    public function testLogsImpersonatorWhenImpersonating(): void
    {
        $user = $this->createUser('jane.doe');
        $impersonator = $this->createUser('admin');

        $originalToken = $this->createStub(TokenInterface::class);
        $originalToken->method('getUser')->willReturn($impersonator);

        $switchUserToken = $this->createStub(SwitchUserToken::class);
        $switchUserToken->method('getOriginalToken')->willReturn($originalToken);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('IS_IMPERSONATOR')
            ->willReturn(true);
        $security->method('getToken')->willReturn($switchUserToken);

        $loggedMessage = null;
        $auditTrailLogger = $this->createMock(LoggerInterface::class);
        $auditTrailLogger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message) use (&$loggedMessage): void {
                $loggedMessage = $message;
            });

        $auditLogger = new AuditLogger($security, new RequestStack(), $auditTrailLogger);
        $auditLogger->log();

        self::assertNotNull($loggedMessage);
        self::assertStringContainsString('User: jane.doe (impersonated by admin)', $loggedMessage);
    }

    public function testDoesNotLogImpersonatorWhenNotImpersonating(): void
    {
        $user = $this->createUser('jane.doe');

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('IS_IMPERSONATOR')
            ->willReturn(false);

        $loggedMessage = null;
        $auditTrailLogger = $this->createMock(LoggerInterface::class);
        $auditTrailLogger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message) use (&$loggedMessage): void {
                $loggedMessage = $message;
            });

        $auditLogger = new AuditLogger($security, new RequestStack(), $auditTrailLogger);
        $auditLogger->log();

        self::assertNotNull($loggedMessage);
        self::assertStringContainsString('User: jane.doe', $loggedMessage);
        self::assertStringNotContainsString('impersonated by', $loggedMessage);
    }

    private function createUser(string $identifier): UserInterface
    {
        return new class ($identifier) implements UserInterface {
            public function __construct(private readonly string $identifier)
            {
            }

            /** @return string[] */
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }
}
