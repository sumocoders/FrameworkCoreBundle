<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

class PageTitle
{
    public function __construct(
        private readonly BreadcrumbTrail $breadcrumbTrail,
        private readonly Fallbacks $fallbacks,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getTitle(): string
    {
        $breadcrumbs = array_reverse($this->breadcrumbTrail->all());

        if (empty($breadcrumbs)) {
            return $this->fallbacks->get('site_title');
        }

        $titles = array_map(fn($breadcrumb) => $this->translator->trans($breadcrumb->getTitle()), $breadcrumbs);
        $titles[] = $this->fallbacks->get('site_title');

        return implode(' - ', $titles);
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
