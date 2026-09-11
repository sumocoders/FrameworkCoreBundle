<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\UserDataBag;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Attaches the current authenticated user's identifier (and, when impersonating, the impersonator's identifier)
 * to the Sentry scope, so errors reported to Sentry are tied to a user.
 *
 * No-ops entirely when the feature is disabled, when no Sentry hub is registered (Sentry is an optional
 * dependency), on sub-requests, or when there is no authenticated user.
 */
class SentryUserContextListener
{
    public function __construct(
        private readonly Security $security,
        private readonly bool $enabled,
        private readonly ?HubInterface $hub = null,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $hub = $this->hub;

        if (!$this->enabled || $hub === null || !$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $impersonatorId = $this->getImpersonatorId();

        $hub->configureScope(static function (Scope $scope) use ($user, $impersonatorId): void {
            $scope->setUser(new UserDataBag(id: $user->getUserIdentifier()));

            if ($impersonatorId !== null) {
                $scope->setContext('impersonation', ['impersonator_id' => $impersonatorId]);
            }
        });
    }

    private function getImpersonatorId(): ?string
    {
        if (!$this->security->isGranted('IS_IMPERSONATOR')) {
            return null;
        }

        $token = $this->security->getToken();
        if (!$token instanceof SwitchUserToken) {
            return null;
        }

        $originalUser = $token->getOriginalToken()->getUser();
        if (!$originalUser instanceof UserInterface) {
            return null;
        }

        return $originalUser->getUserIdentifier();
    }
}
