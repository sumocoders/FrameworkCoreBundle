<?php

namespace SumoCoders\FrameworkCoreBundle\Logger;

use Psr\Log\LoggerInterface;
use SumoCoders\FrameworkCoreBundle\Enum\EventAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditLogger
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $auditTrailLogger,
    ) {
    }

    public function log(
        ?string $entityClass = null,
        ?string $identifier = null,
        EventAction $action = EventAction::READ,
        array $fields = [],
        array $data = []
    ): void {
        $user = $this->getLoggedInUser();
        $imperonatingUser = $this->getImpersonatingUser();
        $userString = $user !== null ? $user->getUserIdentifier() : 'anonymous';
        if ($imperonatingUser !== null) {
            $userString .= sprintf(' (impersonated by %s)', $imperonatingUser->getUserIdentifier());
        }

        $userRoles = implode(', ', $this->getRoles());

        $this->auditTrailLogger->info(
            sprintf(
                'Source: %s; Entity: %s; Identifier: %s; Action: %s; User: %s; Roles: %s; IP: %s; Fields: %s; Data: %s',
                $this->getPathOrCommandName(),
                $entityClass,
                $identifier,
                $action->value,
                $userString,
                $userRoles,
                $this->getIpAddress(),
                json_encode($fields, JSON_THROW_ON_ERROR),
                json_encode($data, JSON_THROW_ON_ERROR)
            )
        );
    }

    private function getIpAddress(): ?string
    {
        if ($this->requestStack->getCurrentRequest() !== null) {
            return $this->requestStack->getCurrentRequest()->getClientIp();
        }

        return null;
    }

    private function getImpersonatingUser(): ?UserInterface
    {
        if ($this->security->isGranted('ROLE_PREVIOUS_ADMIN')) {
            return $this->security->getToken()->getOriginalToken()->getUser();
        }

        return null;
    }

    private function getLoggedInUser(): ?UserInterface
    {
        if ($this->security->getUser() !== null) {
            return $this->security->getUser();
        }

        return null;
    }

    private function getRoles(): array
    {
        if ($this->security->getUser() !== null) {
            return $this->security->getUser()->getRoles();
        }

        return [];
    }

    private function getPathOrCommandName(): string
    {
        // If a request is available, return the current URI
        if ($this->requestStack->getCurrentRequest() !== null) {
            return $this->requestStack->getCurrentRequest()->getUri();
        }

        // Get the console command
        return implode(' ', $_SERVER['argv']);
    }
}
