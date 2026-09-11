[Back to index](index.md)

# Menu

Builds the main navigation tree so each consuming application can contribute its own items without the bundle
knowing about app-specific routes; implemented as a thin KnpMenu factory that dispatches one event and lets listeners
mutate the menu in place.

`src/Menu/MenuBuilder.php`

## KnpMenu factory flow

Registered in `config/services.php` as `framework.menu_builder`, tagged
`knp_menu.menu_builder` with `method: createMainMenu`, `alias: side_menu` — this is how KnpMenuBundle discovers it
(rendered in templates via `{{ knp_menu_render('side_menu') }}`, not shown in this bundle's own files).

`createMainMenu()`:
1. `$factory->createItem('root')`, sets `nav navbar-nav` as the root's `childrenAttribute('class')`.
2. Dispatches `ConfigureMenuEvent` (see below) — listeners add/mutate children on `$menu` directly; the event carries
   no return value, mutation is the only channel.
3. `reorderMenuItems($menu)` — recursive post-processing pass, see below.

## `ConfigureMenuEvent` — the extension point

`src/Event/ConfigureMenuEvent.php` — extends Symfony's `Contracts\EventDispatcher\Event` (not stoppable), dispatched
under `ConfigureMenuEvent::EVENT_NAME = 'framework_core.configure_menu'`. Carries `getFactory(): FactoryInterface` and
`getMenu(): ItemInterface`; `setFactory()`/`setMenu()` exist but are `private` and unused anywhere in this repo — dead
code, not a usable API (getters are the only public surface). Consuming apps listen with
`{ name: kernel.event_listener, event: framework_core.configure_menu, method: onConfigureMenu }` per `docs/menu.md`.

## `reorderMenuItems()` — undocumented `orderNumber` extra

Recurses into every child with its own children first, then reorders `$menu`'s direct children based on each child's
`orderNumber` extra (`$menuItem->getExtra('orderNumber')`) — **this extra is not mentioned anywhere in
`docs/menu.md`**; it's an internal-only convention a listener can set via
`$factory->createItem($label, ['extras' => ['orderNumber' => 10]])`.

- Items are bucketed: those with an `orderNumber` go into `$menuOrderArray[$orderNumber] = $itemName`; those without
  go into `$addLast` (appended, in original order, after all numbered items).
- Collision (two items claim the same `orderNumber`): the second one is stashed in `$alreadyTaken` and, after
  `ksort()`, spliced into the ordered array immediately **before** the position of the number it collided with —
  collisions don't error, they just get inserted adjacent to the item they collided with.
- `ItemInterface::reorderChildren()` is only called at all when `$menuOrderArray` is non-empty — if **no** child has
  an `orderNumber`, the menu keeps KnpMenu's natural insertion order from whatever sequence listeners ran in (listener
  priority order), untouched.

## `DefaultMenuListener` — base class, not a registered service

`src/EventListener/DefaultMenuListener.php` is **not** tagged or registered anywhere in `config/services.php` — it's
a plain class meant to be `extends`-ed by an app-specific listener (per `docs/menu.md`'s example), which then
implements `EventSubscriberInterface` itself and subscribes to `ConfigureMenuEvent::EVENT_NAME`. Extending it gives
autowired access to three collaborators via getters (`getSecurity()`, `getTranslator()`, `getRequestStack()`) plus:

- `enableChildRoutes(ItemInterface $item, string $prefix)` — if the current request's `_route` attribute
  `str_contains()`s `$prefix`, sets `$item`'s `routes` extra to `[['route' => $currentRouteName]]`. This bundle does
  not itself read that `routes` extra anywhere (confirmed: no other file in this repo consumes it) — it's set for
  KnpMenuBundle's own built-in "current item" voter to read at render time, which is what drives the `current`
  CSS/state KnpMenuBundle applies. If KnpMenuBundle's voter configuration changes, this helper's effect changes with
  it, invisibly to this bundle's code.
- If `RequestStack::getCurrentRequest()` is `null` (no active request, e.g. a console command building a menu),
  `enableChildRoutes()` silently no-ops.

## Rendering

`templates/Menu/menu.html.twig` extends KnpMenuBundle's `knp_menu.html.twig` and overrides the `label`/`item` blocks —
reads `labelAttributes.icon`, `attributes.pill`, and the `translation_params`/`translation_domain` extras for the
label; applies Bootstrap dropdown markup when `item.parent.name == 'root' && item.hasChildren`. This template consumes
item **attributes**/**extras** set by consuming-app listeners (icon, pill badge, translation params) but not
`orderNumber` or `routes` — those two are consumed by `MenuBuilder` (build-time) and KnpMenuBundle's voter
(render-time) respectively, not by this template.
