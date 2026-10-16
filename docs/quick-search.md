# Quick search

Cmd+K (Mac) or Ctrl+K (Windows/Linux) opens a search modal on every page that extends the bundle's `base.html.twig`.
Results come from *providers*: the bundle ships one for the main menu, applications add their own (orders, customers,
...). All results are shown in one list, ranked on relevance, each with a badge naming its source.

## Prerequisites

- `symfony/ux-live-component` (required by the bundle). Its recipe adds `config/routes/ux_live_component.yaml`, which
  the search needs to re-render as you type; check that file exists in your application.
- The `search-modal` Stimulus controller registered in your application, like the other bundle controllers:

  ```php
  // importmap.php
  'sumocoders/SearchModal' => ['path' => './vendor/sumocoders/framework-core-bundle/assets-public/controllers/search_modal_controller.js'],
  ```

  ```js
  // assets/bootstrap.js
  import SearchModal from 'sumocoders/SearchModal'

  app.register('search-modal', SearchModal)
  ```

## Usage

Out of the box the modal searches the labels of the main menu (submenus included, shown as "Parent › Child"). Type 2
characters or more; results update 300 ms after you stop typing. Arrow keys move through the results, Enter opens
one, Escape closes the modal.

### Adding a provider

Implement `QuickSearchProvider`. Autoconfigured services (the default in `config/services.yaml`) are tagged
automatically; nothing else to register.

```php
<?php

namespace App\Service\QuickSearch;

use App\Repository\Order\OrderRepository;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider;
use SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Scorer\GivenOrderScorer;
use SumoCoders\FrameworkCoreBundle\ValueObject\QuickSearchResult;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class OrderQuickSearchProvider implements QuickSearchProvider
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function search(string $query, int $limit): iterable
    {
        // The repository returns the orders newest first; score them in that order.
        $orders = $this->orderRepository->findForQuickSearch($query, $limit);
        $scores = GivenOrderScorer::score(count($orders));

        foreach ($orders as $index => $order) {
            yield new QuickSearchResult(
                $order->getOrderNumber(),
                $this->urlGenerator->generate('order_show', ['order' => $order->getId()]),
                'cart',
                $this->translator->trans('Orders'),
                $scores[$index],
            );
        }
    }

    public function getMultiplier(): float
    {
        return 1.0;
    }
}
```

Keep the query in the repository (it returns entities) and build URLs in the provider.

### Ranking

Every result has a `relevanceScore` from 0 to 1, every provider a multiplier. The list is sorted on score times
multiplier, highest first; equal scores are sorted alphabetically on text. A multiplier above 1 lifts a provider's
results, below 1 lowers them.

Two scorers turn a provider's values into scores:

| Scorer               | Input                        | Scores                                                             |
|----------------------|------------------------------|--------------------------------------------------------------------|
| `AlphabeticalScorer` | `list<string>`               | Alphabetical rank: the first 1, every next one 0.9 times the one before; equal texts score the same |
| `GivenOrderScorer`   | `int` (the number of results) | Position: the first 1, every next one 0.9 times the one before   |

Both return one score per result in the order of their input, so you can pair them with your results by index.
`GivenOrderScorer` fits results a query already sorted (newest first, by name, ...). Need something else, like
scoring on age? Compute your own score between 0 and 1. Without a scorer, a result gets the default score 0.5.

### Options

| Option                            | Meaning                                                      | Default                     |
|-----------------------------------|--------------------------------------------------------------|-----------------------------|
| `QuickSearchResult::$text`        | Text of the result                                           | required                    |
| `QuickSearchResult::$url`         | Where the result links to                                    | required                    |
| `QuickSearchResult::$icon`        | Bootstrap icon name without `bi-`, e.g. `cart`               | required                    |
| `QuickSearchResult::$source`      | Badge naming where the result comes from                     | required                    |
| `QuickSearchResult::$relevanceScore` | 0 to 1, multiplied by the provider's multiplier           | 0.5                         |
| `getMultiplier()`                 | Weighs a provider against the others                         | 1.0 is neutral; menu 1.2    |
| `$limit` in `search()`            | Results a provider should return at most                     | 10                          |

### Switching it off

Override the block in your `templates/base.html.twig`:

```twig
{% block quick_search %}{% endblock %}
```

## Internals

- `QuickSearch` (`framework.quick_search`) gets every service tagged with the `QuickSearchProvider` interface name
  (`tagged_iterator` in `config/services.php`). The tag is added by
  `SumoCodersFrameworkCoreExtension::load()` through `registerForAutoconfiguration()`: an `#[AutoconfigureTag]` on the
  interface would only work for classes found by resource scanning, which the bundle doesn't do.
- Each provider gets the full limit; `QuickSearch` merges, sorts and returns everything, there is no overall cut.
- The live component is named `SumoCodersFrameworkCore:QuickSearch`. Twig Components derives an anonymous component
  with that same name from `templates/components/QuickSearch.html.twig`; the live component replaces it, and the
  prefix keeps it apart from an application's own `QuickSearch` component.
- The modal lives in `templates/quick_search.html.twig`, included from `base.html.twig` in the `quick_search` block.
  It has no `fade` class: `close()` runs on `turbo:before-cache`, and without the transition the modal is gone before
  Turbo snapshots the page, so the back button never shows a stale backdrop.
- The shortcut is bound on `window`, so it also works while focus is in another input, and the browser's own Cmd+K
  is prevented.
- `MenuQuickSearchProvider` builds the menu through `framework.menu_builder` on every search, translates labels the
  way `templates/Menu/menu.html.twig` renders them (`trans` with the item's `translation_params` and
  `translation_domain` extras, then `ucfirst`; a `false` domain keeps the label untranslated), skips items with
  `display` off and submenu parents linking to `#`. Items your menu listener doesn't add for the current user don't
  show up either. Results take a Bootstrap icon name, so an item without an icon or with a Font Awesome icon gets the
  menu's fallback, `circle-fill`.
- Both scorers share `GivenOrderScorer::DROP_OFF` (0.9), so a given rank scores the same whichever scorer produced it.

## Troubleshooting

- **Shortcut does nothing**: the `search-modal` controller isn't registered, see Prerequisites.
- **Results don't update while typing**: the `ux_live_component` route is missing; check
  `config/routes/ux_live_component.yaml` and `debug:router | grep _components`.
- **Two search modals**: the application still renders its own; remove it or override the `quick_search` block.
- **A provider's results never show up**: the service isn't autoconfigured; tag it with the
  `SumoCoders\FrameworkCoreBundle\Service\QuickSearch\Provider\QuickSearchProvider` tag.
