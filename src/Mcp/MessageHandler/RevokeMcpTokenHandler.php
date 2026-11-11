<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\MessageHandler;

use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Message\RevokeMcpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Repository\McpTokenRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RevokeMcpTokenHandler
{
    public function __construct(
        private McpTokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(RevokeMcpToken $message): void
    {
        $token = $this->tokenRepository->find($message->tokenId);
        if (!$token instanceof McpToken) {
            throw new \InvalidArgumentException('MCP token not found.');
        }

        $this->tokenRepository->remove($token);
    }
}
