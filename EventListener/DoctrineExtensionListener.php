<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Gedmo\Blameable\BlameableListener;
use Gedmo\Loggable\LoggableListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DoctrineExtensionListener
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var LoggableListener */
    private $loggableListener;

    /** @var BlameableListener */
    private $blameableListener;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        LoggableListener $loggableListener,
        BlameableListener $blameableListener
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->loggableListener = $loggableListener;
        $this->blameableListener = $blameableListener;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->tokenStorage->getToken() !== null && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->tokenStorage->getToken()->getUser();

            $this->loggableListener->setUsername($user);
            $this->blameableListener->setUserValue($user);
        }
    }
}
