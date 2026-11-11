<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\Security;

use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A user may revoke their own tokens, ROLE_ADMIN may revoke every token.
 *
 * @extends Voter<string, McpToken>
 */
final class McpTokenVoter extends Voter
{
    public const string REVOKE = 'MCP_TOKEN_REVOKE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::REVOKE && $subject instanceof McpToken;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        #[\SensitiveParameter]
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($subject->getUser()->getUserIdentifier() === $user->getUserIdentifier()) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN']);
    }
}
