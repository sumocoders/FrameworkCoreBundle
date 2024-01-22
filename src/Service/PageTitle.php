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

    private ?string $title = null;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }

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
