<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Tests\Service\QuickSearch;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\QuickSearch;
use SumoCoders\FrameworkCoreBundle\Tests\Service\QuickSearch\Provider\FakeQuickSearchProvider;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;

final class QuickSearchTest extends TestCase
{
    public function testItSortsOnScoreTimesMultiplierThenAlphabetically(): void
    {
        $quickSearch = new QuickSearch([
            new FakeQuickSearchProvider(1.0, ['Order low' => 0.2, 'Order high' => 1.0, 'Beta' => 0.5]),
            new FakeQuickSearchProvider(2.0, ['Menu' => 0.4, 'Alpha' => 0.25]),
        ]);

        static::assertSame(
            ['Order high', 'Menu', 'Alpha', 'Beta', 'Order low'],
            array_map(static fn (QuickSearchResult $result): string => $result->text, $quickSearch->search('x', 10)),
        );
    }
}
