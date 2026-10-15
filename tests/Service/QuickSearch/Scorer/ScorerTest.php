<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Tests\Service\QuickSearch\Scorer;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Scorer\AlphabeticalScorer;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Scorer\GivenOrderScorer;

final class ScorerTest extends TestCase
{
    public function testAlphabeticalScorerRanksTextsInTheOrderGiven(): void
    {
        static::assertEqualsWithDelta(
            [0.81, 1.0, 0.729, 1.0],
            AlphabeticalScorer::score(['banaan', 'Appel', 'citroen', 'appel']),
            1e-9,
        );
    }

    public function testAlphabeticalScorerComparesNumbersNaturally(): void
    {
        static::assertEqualsWithDelta([0.9, 1.0], AlphabeticalScorer::score(['item 10', 'item 9']), 1e-9);
    }

    public function testAlphabeticalScorerNeverDropsToZero(): void
    {
        $scores = AlphabeticalScorer::score(array_map(
            static fn (int $i): string => sprintf('item %03d', $i),
            range(1, 50),
        ));

        static::assertGreaterThan(0.0, $scores[49]);
        static::assertGreaterThan($scores[49], $scores[48]);
    }

    public function testGivenOrderScorerScoresByPosition(): void
    {
        static::assertEqualsWithDelta([1.0, 0.9, 0.81], GivenOrderScorer::score(3), 1e-9);
    }

    public function testScorersReturnNothingForNothing(): void
    {
        static::assertSame([], AlphabeticalScorer::score([]));
        static::assertSame([], GivenOrderScorer::score(0));
    }
}
