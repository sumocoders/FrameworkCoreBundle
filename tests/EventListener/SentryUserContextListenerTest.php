<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use SumoCoders\FrameworkCoreBundle\EventListener\SentryUserContextListener;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SentryUserContextListenerTest extends TestCase
{
    public function testDoesNothingWhenDisabled(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('configureScope');

        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('getUser');

        $listener = new SentryUserContextListener($security, false, $hub);
        $listener->onKernelRequest($this->createMainRequestEvent());
    }

    public function testDoesNothingWhenHubIsNull(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('getUser');

        $listener = new SentryUserContextListener($security, true, null);
        $listener->onKernelRequest($this->createMainRequestEvent());
    }

    public function testDoesNothingWithoutAuthenticatedUser(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('configureScope');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $listener = new SentryUserContextListener($security, true, $hub);
        $listener->onKernelRequest($this->createMainRequestEvent());
    }

    public function testSetsUserOnScopeForAuthenticatedUser(): void
    {
        $user = $this->createUser('jane.doe');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(false);

        $scope = new Scope();
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method('configureScope')
            ->willReturnCallback(static function (callable $callback) use ($scope): void {
                $callback($scope);
            });

        $listener = new SentryUserContextListener($security, true, $hub);
        $listener->onKernelRequest($this->createMainRequestEvent());

        self::assertNotNull($scope->getUser());
        self::assertSame($user->getUserIdentifier(), $scope->getUser()->getId());
    }

    public function testAddsImpersonationContextWhenImpersonating(): void
    {
        $user = $this->createUser('jane.doe');
        $impersonator = $this->createUser('admin');

        $originalToken = $this->createStub(TokenInterface::class);
        $originalToken->method('getUser')->willReturn($impersonator);

        $switchUserToken = $this->createStub(SwitchUserToken::class);
        $switchUserToken->method('getOriginalToken')->willReturn($originalToken);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(true);
        $security->method('getToken')->willReturn($switchUserToken);

        $scope = new Scope();
        $hub = $this->createStub(HubInterface::class);
        $hub->method('configureScope')->willReturnCallback(static function (callable $callback) use ($scope): void {
            $callback($scope);
        });

        $listener = new SentryUserContextListener($security, true, $hub);
        $listener->onKernelRequest($this->createMainRequestEvent());

        $appliedEvent = $scope->applyToEvent(Event::createEvent(), null, null);

        self::assertNotNull($appliedEvent);
        self::assertSame(
            ['impersonator_id' => 'admin'],
            $appliedEvent->getContexts()['impersonation'] ?? null,
        );
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

    private function createMainRequestEvent(): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);
    }
}
