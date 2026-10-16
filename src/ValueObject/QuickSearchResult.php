<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

/**
 * One hit in the quick search modal (see docs/quick-search.md). The icon is a Bootstrap icon name without the `bi-`
 * prefix, the source is the badge naming where the hit comes from. The relevance score (0 to 1) is multiplied by the
 * provider's multiplier to rank the hit among all others.
 */
final readonly class QuickSearchResult
{
    public function __construct(
        public string $text,
        public string $url,
        public string $icon,
        public string $source,
        public float $relevanceScore = 0.5,
    ) {
        assert(
            $relevanceScore >= 0.0 && $relevanceScore <= 1.0,
            'The relevance score of a quick search result must be between 0 and 1.',
        );
    }
}
