<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\Message;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Handle this message synchronously: the handler returns the plain token, the only moment it is known.
 */
final class CreateMcpToken
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    public function __construct(
        public readonly UserInterface $user,
    ) {
    }
}
