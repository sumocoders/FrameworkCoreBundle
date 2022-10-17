<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Gedmo\Blameable\BlameableListener;
use Gedmo\Loggable\LoggableListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DoctrineExtensionListener
{
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private LoggableListener $loggableListener;
    private BlameableListener $blamableListener;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        LoggableListener $loggableListener,
        BlameableListener $blamableListener
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->loggableListener = $loggableListener;
        $this->blamableListener = $blamableListener;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (
            $this->tokenStorage->getToken() !== null
            && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $user = $this->tokenStorage->getToken()->getUser();

            $this->loggableListener->setUsername($user);
            $this->blamableListener->setUserValue($user);
        }
    }
}
