<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\Security;

use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Repository\McpTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticates every request on a stateless MCP firewall with a personal bearer token (McpToken). The request
 * then runs as the token's user, with that user's roles.
 */
final class McpTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly McpTokenRepository $tokenRepository,
    ) {
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        // The auth scheme is case-insensitive (RFC 9110), so "bearer" works too.
        $matches = [];
        $header = trim((string) $request->headers->get('Authorization', ''));
        $plainToken = preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1 ? $matches[1] : '';
        if ($plainToken === '') {
            throw new CustomUserMessageAuthenticationException('Missing bearer token.');
        }

        $token = $this->tokenRepository->findOneByPlainToken($plainToken);
        if (!$token instanceof McpToken) {
            throw new CustomUserMessageAuthenticationException('Invalid bearer token.');
        }

        $token->markUsed(new \DateTimeImmutable());
        $this->tokenRepository->flush();

        $user = $token->getUser();

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), static fn (): UserInterface => $user),
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        #[\SensitiveParameter]
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        // Let the request through to the MCP endpoint.
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->unauthorized();
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthorized();
    }

    private function unauthorized(): Response
    {
        return new JsonResponse(
            ['error' => 'Unauthorized'],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer'],
        );
    }
}
