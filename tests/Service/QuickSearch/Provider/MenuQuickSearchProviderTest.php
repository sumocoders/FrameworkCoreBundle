<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Tests\Service\QuickSearch\Provider;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\Menu\MenuBuilder;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\MenuQuickSearchProvider;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

final class MenuQuickSearchProviderTest extends TestCase
{
    public function testItMatchesTranslatedLabelsAlphabetically(): void
    {
        $results = self::search('order');

        static::assertSame(
            ['Orders /orders cart 1', 'Unpaid orders /unpaid cash 0.9'],
            array_map(
                static fn (QuickSearchResult $result): string => sprintf(
                    '%s %s %s %s',
                    $result->text,
                    $result->url,
                    $result->icon,
                    $result->relevanceScore,
                ),
                $results,
            ),
        );
        static::assertSame('core.quick_search.source.navigation', $results[0]->source);
    }

    public function testItSearchesSubmenusButNotTheirParentLink(): void
    {
        static::assertSame(
            ['Users › List', 'Users › Roles'],
            array_map(static fn (QuickSearchResult $result): string => $result->text, self::search('users')),
        );
    }

    public function testItSkipsHiddenItems(): void
    {
        static::assertSame([], self::search('secret'));
    }

    public function testItTranslatesLabelsUnlessTheDomainIsFalse(): void
    {
        static::assertSame(['Invoices'], self::texts('invoices'));
        static::assertSame(['Reports'], self::texts('reports'));
        static::assertSame([], self::texts('translated'));
    }

    /**
     * @return list<string>
     */
    private static function texts(string $query): array
    {
        return array_map(static fn (QuickSearchResult $result): string => $result->text, self::search($query));
    }

    /**
     * @return list<QuickSearchResult>
     */
    private static function search(string $query): array
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ConfigureMenuEvent::EVENT_NAME, static function (ConfigureMenuEvent $event): void {
            $menu = $event->getMenu();
            $menu->addChild('orders', ['uri' => '/orders', 'labelAttributes' => ['icon' => 'bi bi-cart']]);
            $menu->addChild('unpaid orders', ['uri' => '/unpaid', 'labelAttributes' => ['icon' => 'bi bi-cash']]);
            $menu->addChild('secret', ['uri' => '/secret'])->setDisplay(false);
            $users = $menu->addChild('users', ['uri' => '#']);
            $users->addChild('list', ['uri' => '/users']);
            $users->addChild('roles', ['uri' => '/roles']);
            $menu->addChild('menu.billing', ['uri' => '/invoices']);
            $menu->addChild('reports', ['uri' => '/reports', 'extras' => ['translation_domain' => false]]);
        });
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['menu.billing' => 'invoices', 'reports' => 'translated reports'], 'en');
        $provider = new MenuQuickSearchProvider(new MenuBuilder(new MenuFactory(), $dispatcher), $translator);

        return $provider->search($query, 10);
    }
}
