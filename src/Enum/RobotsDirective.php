<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum RobotsDirective: string implements TranslatableInterface
{
    case Index = 'index';
    case NoIndex = 'noindex';
    case Follow = 'follow';
    case NoFollow = 'nofollow';
    case NoArchive = 'noarchive';
    case NoSnippet = 'nosnippet';
    case NoImageIndex = 'noimageindex';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('seo.robots.' . $this->value, locale: $locale);
    }
}
