<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Scorer;

/**
 * Scores results in the order they are given, e.g. as a repository already sorted them: the first scores 1, every
 * next one DROP_OFF times the one before (1, 0.9, 0.81, ...), never 0.
 */
final class GivenOrderScorer
{
    public const float DROP_OFF = 0.9;

    /**
     * @return list<float> the score of each position, first to last
     */
    public static function score(int $count): array
    {
        $scores = [];
        for ($position = 0; $position < $count; $position++) {
            $scores[] = self::DROP_OFF ** $position;
        }

        return $scores;
    }
}
