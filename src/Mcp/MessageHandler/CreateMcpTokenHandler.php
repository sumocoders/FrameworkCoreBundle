<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\MessageHandler;

use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Message\CreateMcpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Repository\McpTokenRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateMcpTokenHandler
{
    public function __construct(
        private McpTokenRepository $tokenRepository,
        #[Autowire(param: 'sumo_coders_framework_core.mcp_tokens.token_prefix')]
        private string $tokenPrefix,
    ) {
    }

    /**
     * @return string the plain token, the only moment it is known
     */
    public function __invoke(CreateMcpToken $message): string
    {
        $plainToken = $this->tokenPrefix . bin2hex(random_bytes(32));

        $this->tokenRepository->add(new McpToken(
            user: $message->user,
            name: (string) $message->name,
            tokenHash: McpToken::hash($plainToken),
            createdAt: new \DateTimeImmutable(),
        ));

        return $plainToken;
    }
}
