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
        $breadcrumbs = $this->breadcrumbTrail->all();
        $breadcrumbs =array_reverse($breadcrumbs);

        if (count($breadcrumbs) === 0) {
            return $this->fallbacks->get('site_title');
        }

        $title = '';
        foreach ($breadcrumbs as $breadcrumb) {
            $title .= $this->translator->trans($breadcrumb->getTitle()).' - ';
        }

        $title .= $this->fallbacks->get('site_title');

        return $title;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
