<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider;

use Knp\Menu\ItemInterface;
use SumoCoders\FrameworkCoreBundle\Menu\MenuBuilder;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Scorer\AlphabeticalScorer;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Matches the translated labels of the main menu, submenus included, scored alphabetically.
 */
final readonly class MenuQuickSearchProvider implements QuickSearchProvider
{
    public function __construct(
        private MenuBuilder $menuBuilder,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<QuickSearchResult>
     */
    public function search(string $query, int $limit): iterable
    {
        $matches = [];
        $this->collect($this->menuBuilder->createMainMenu(), [], $query, $matches);

        // All matches, not capped at $limit: the menu is short and every hit is useful.
        $scores = AlphabeticalScorer::score(array_column($matches, 'text'));
        $source = $this->translator->trans('core.quick_search.source.navigation');
        $results = [];
        foreach ($matches as $index => $match) {
            $results[] = new QuickSearchResult($match['text'], $match['url'], $match['icon'], $source, $scores[$index]);
        }

        return $results;
    }

    /**
     * Slightly above the neutral 1.0, so a matching screen tends to rank above other results.
     */
    public function getMultiplier(): float
    {
        return 1.2;
    }

    /**
     * @param list<string> $parents translated labels of the parent items
     * @param list<array{text: string, url: string, icon: string}> $matches
     */
    private function collect(ItemInterface $menu, array $parents, string $query, array &$matches): void
    {
        foreach ($menu->getChildren() as $item) {
            if (!$item->isDisplayed()) {
                continue;
            }

            $path = [...$parents, $this->translateLabel($item)];
            $url = $item->getUri();
            // Parents of a submenu link to '#'; only their children lead somewhere.
            if ($url !== null && $url !== '#' && mb_stripos(implode(' ', $path), $query) !== false) {
                $icon = $item->getLabelAttribute('icon');
                $matches[] = [
                    'text' => implode(' › ', $path),
                    'url' => $url,
                    // Results take a Bootstrap icon name; anything else gets the menu's own fallback icon.
                    'icon' => is_string($icon) && str_starts_with($icon, 'bi bi-') ? substr($icon, 6) : 'circle-fill',
                ];
            }

            $this->collect($item, $path, $query, $matches);
        }
    }

    /**
     * Translated the same way templates/Menu/menu.html.twig renders the label.
     */
    private function translateLabel(ItemInterface $item): string
    {
        // @mago-expect analysis:mixed-assignment
        $parameters = $item->getExtra('translation_params', []);
        // @mago-expect analysis:mixed-assignment
        $domain = $item->getExtra('translation_domain', 'messages');
        // A false domain means "don't translate", the label is shown as is.
        if ($domain === false) {
            return ucfirst($item->getLabel());
        }

        return ucfirst($this->translator->trans(
            $item->getLabel(),
            is_array($parameters) ? $parameters : [],
            is_string($domain) ? $domain : 'messages',
        ));
    }
}
