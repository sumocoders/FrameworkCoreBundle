<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Mcp\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<McpToken>
 */
class McpTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, McpToken::class);
    }

    public function add(#[\SensitiveParameter] McpToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function remove(#[\SensitiveParameter] McpToken $token): void
    {
        $this->getEntityManager()->remove($token);
        $this->getEntityManager()->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findOneByPlainToken(#[\SensitiveParameter] string $plainToken): ?McpToken
    {
        return $this->findOneBy(['tokenHash' => McpToken::hash($plainToken)]);
    }

    /**
     * @return list<McpToken>
     */
    public function findForUser(UserInterface $user): array
    {
        return $this->findBy(['user' => $user], ['name' => 'ASC', 'createdAt' => 'DESC']);
    }

    /**
     * All tokens of all users, with the user fetched in the same query. Meant for an admin overview.
     *
     * @return list<McpToken>
     */
    public function findAllWithUser(): array
    {
        /** @var list<McpToken> */
        return $this
            ->createQueryBuilder('t')
            ->addSelect('u')
            ->join('t.user', 'u')
            ->orderBy('t.name')
            ->addOrderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
