<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Scorer;

/**
 * Scores texts by alphabetical rank, with the same drop-off as GivenOrderScorer: the first scores 1, every next rank
 * 0.9 times the one before (1, 0.9, 0.81, ...), never 0. Equal texts score the same.
 */
final class AlphabeticalScorer
{
    /**
     * @param list<string> $texts
     *
     * @return list<float> the score of each text, in the order given
     */
    public static function score(array $texts): array
    {
        $scores = [];
        foreach ($texts as $text) {
            // The number of texts sorting before this one is its rank.
            $rank = count(array_filter($texts, static fn (string $other): bool => strnatcasecmp($other, $text) < 0));
            $scores[] = GivenOrderScorer::DROP_OFF ** $rank;
        }

        return $scores;
    }
}
