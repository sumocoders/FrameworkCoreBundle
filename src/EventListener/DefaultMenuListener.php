<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultMenuListener
{
    private Security $security;
    private TranslatorInterface $translator;

    public function __construct(
        Security $security,
        TranslatorInterface $translator
    ) {
        $this->security = $security;
        $this->translator = $translator;
    }

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
