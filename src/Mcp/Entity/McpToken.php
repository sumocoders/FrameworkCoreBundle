<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\Entity;

use Doctrine\ORM\Mapping as ORM;
use SumoCoders\FrameworkCoreBundle\Mcp\Repository\McpTokenRepository;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A personal bearer token for the MCP endpoint. The table only stores the SHA-256 hash of the token, the plain
 * token exists once, right after creating it.
 *
 * The user relation targets UserInterface, the bundle resolves it to `mcp_tokens.user_class`.
 */
#[ORM\Entity(repositoryClass: McpTokenRepository::class)]
#[ORM\Table(name: 'mcp_api_token')]
class McpToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: UserInterface::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private readonly UserInterface $user,
        #[ORM\Column(length: 255)]
        private readonly string $name,
        #[ORM\Column(length: 64, unique: true)]
        private readonly string $tokenHash,
        #[ORM\Column]
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function hash(#[\SensitiveParameter] string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function markUsed(\DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
