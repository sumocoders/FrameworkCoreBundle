<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Knp\Menu\MenuFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DefaultMenuListener
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $securityAuthorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $securityTokenStorage;

    /**
     * @param AuthorizationCheckerInterface $securityAuthorizationChecker
     * @param TokenStorageInterface         $securityTokenStorage
     */
    public function __construct(
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage
    ) {
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->securityTokenStorage = $securityTokenStorage;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getSecurityAuthorizationChecker()
    {
        return $this->securityAuthorizationChecker;
    }

    /**
     * @return TokenStorageInterface
     */
    public function getSecurityTokenStorage()
    {
        return $this->securityTokenStorage;
    }
}
