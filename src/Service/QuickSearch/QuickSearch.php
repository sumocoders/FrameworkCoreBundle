<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Service\QuickSearch;

use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;

/**
 * Collects the results of all quick search providers (see docs/quick-search.md) into one list, sorted on relevance
 * score times the provider's multiplier (high first), then alphabetically on text. Every provider gets the full limit.
 */
final readonly class QuickSearch
{
    /**
     * @param iterable<QuickSearchProvider> $providers
     */
    public function __construct(
        private iterable $providers,
    ) {
    }

    /**
     * @return list<QuickSearchResult>
     */
    public function search(string $query, int $limit): array
    {
        $results = [];
        $scores = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->search($query, $limit) as $result) {
                $results[] = $result;
                $scores[] = $result->relevanceScore * $provider->getMultiplier();
            }
        }

        $order = array_keys($results);
        usort($order, static function (int $a, int $b) use ($results, $scores): int {
            $byScore = $scores[$b] <=> $scores[$a];

            return $byScore !== 0 ? $byScore : strnatcasecmp($results[$a]->text, $results[$b]->text);
        });

        return array_map(static fn (int $index): QuickSearchResult => $results[$index], $order);
    }
}
