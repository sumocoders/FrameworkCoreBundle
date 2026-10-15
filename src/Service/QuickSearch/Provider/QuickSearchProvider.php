<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider;

use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;

/**
 * A source of quick search results (see docs/quick-search.md). Every autoconfigured implementation is tagged with this
 * interface's name by SumoCodersFrameworkCoreExtension.
 */
interface QuickSearchProvider
{
    /**
     * @return iterable<QuickSearchResult>
     */
    public function search(string $query, int $limit): iterable;

    /**
     * Multiplies the relevance score of this provider's results, to rank them higher (> 1) or lower (< 1) than others.
     */
    public function getMultiplier(): float;
}
