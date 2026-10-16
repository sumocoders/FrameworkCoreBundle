<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Tests\Service\QuickSearch\Provider;

use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;

final readonly class FakeQuickSearchProvider implements QuickSearchProvider
{
    /**
     * @param array<string, float> $scores relevance score per result text
     */
    public function __construct(
        private float $multiplier,
        private array $scores,
    ) {
    }

    public function search(string $query, int $limit): iterable
    {
        foreach ($this->scores as $text => $score) {
            yield new QuickSearchResult($text, '/' . $text, 'search', 'Test', $score);
        }
    }

    public function getMultiplier(): float
    {
        return $this->multiplier;
    }
}
