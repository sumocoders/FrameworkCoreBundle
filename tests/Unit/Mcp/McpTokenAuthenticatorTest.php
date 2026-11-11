<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Unit\Mcp;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Repository\McpTokenRepository;
use SumoCoders\FrameworkCoreBundle\Mcp\Security\McpTokenAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class McpTokenAuthenticatorTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null}>
     */
    public static function missingTokenHeaders(): iterable
    {
        yield 'no header' => [null];
        yield 'empty header' => [''];
        yield 'other scheme' => ['Basic dXNlcjpwYXNz'];
        yield 'scheme only' => ['Bearer '];
    }

    #[DataProvider('missingTokenHeaders')]
    public function testRejectsRequestWithoutBearerToken(?string $header): void
    {
        $repository = $this->createMock(McpTokenRepository::class);
        $repository->expects($this->never())->method('findOneByPlainToken');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Missing bearer token.');

        new McpTokenAuthenticator($repository)->authenticate($this->createRequest($header));
    }

    public function testRejectsUnknownToken(): void
    {
        $repository = $this->createStub(McpTokenRepository::class);
        $repository->method('findOneByPlainToken')->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid bearer token.');

        new McpTokenAuthenticator($repository)->authenticate($this->createRequest('Bearer unknown'));
    }

    public function testAuthenticatesAsTokenUserAndMarksTokenUsed(): void
    {
        $user = new InMemoryUser('jane.doe', null, ['ROLE_USER']);
        $token = new McpToken($user, 'Laptop', McpToken::hash('mcp_secret'), new \DateTimeImmutable('-1 day'));

        $repository = $this->createMock(McpTokenRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneByPlainToken')
            ->with('mcp_secret')
            ->willReturn($token);
        $repository->expects($this->once())->method('flush');

        // The auth scheme is case-insensitive.
        $passport = new McpTokenAuthenticator($repository)->authenticate($this->createRequest('bearer mcp_secret'));

        $badge = $passport->getBadge(UserBadge::class);
        static::assertInstanceOf(UserBadge::class, $badge);
        static::assertSame('jane.doe', $badge->getUserIdentifier());
        static::assertSame($user, $passport->getUser());
        static::assertNotNull($token->getLastUsedAt());
    }

    public function testFailureReturnsBearerChallenge(): void
    {
        $authenticator = new McpTokenAuthenticator($this->createStub(McpTokenRepository::class));

        $response = $authenticator->onAuthenticationFailure(new Request(), new AuthenticationException());

        static::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        static::assertSame('Bearer', $response->headers->get('WWW-Authenticate'));
        static::assertSame(Response::HTTP_UNAUTHORIZED, $authenticator->start(new Request())->getStatusCode());
    }

    private function createRequest(?string $authorization): Request
    {
        $request = new Request();
        if ($authorization !== null) {
            $request->headers->set('Authorization', $authorization);
        }

        return $request;
    }
}
