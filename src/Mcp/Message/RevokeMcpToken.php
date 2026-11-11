<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\Message;

/**
 * Check McpTokenVoter::REVOKE before dispatching: the handler itself does no authorization.
 */
final readonly class RevokeMcpToken
{
    public function __construct(
        public int $tokenId,
    ) {
    }
}
