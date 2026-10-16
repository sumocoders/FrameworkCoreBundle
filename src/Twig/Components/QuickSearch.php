<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Twig\Components;

use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\QuickSearch as QuickSearchService;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The search field and results of the Cmd+K modal (see docs/quick-search.md). Named after the bundle, like the
 * anonymous components Twig Components derives from templates/components/, so it can't clash with an application's.
 */
#[AsLiveComponent(
    name: 'SumoCodersFrameworkCore:QuickSearch',
    template: '@SumoCodersFrameworkCore/components/QuickSearch.html.twig',
)]
final class QuickSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(
        private readonly QuickSearchService $quickSearch,
    ) {
    }

    /**
     * Null while the query is too short to search, so the template shows nothing instead of "no results".
     *
     * @return list<QuickSearchResult>|null
     */
    public function getResults(): ?array
    {
        $query = trim($this->query);
        if (mb_strlen($query) < 2) {
            return null;
        }

        return $this->quickSearch->search($query, 10);
    }
}
