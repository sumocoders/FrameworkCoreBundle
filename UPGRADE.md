# Upgrade guide

Sections are newest first. Each one lists what to do when upgrading to that version line, patch releases included.

## To 19.2

**Quick search (Cmd+K / Ctrl+K).** Every page on the bundle's `base.html.twig` now has a search modal over the main
menu, and applications can add their own sources. See [docs/quick-search.md](docs/quick-search.md). The bundle now
requires `symfony/ux-live-component`. To finish the update:

1. Check `config/routes/ux_live_component.yaml` exists (Flex adds it with the package's recipe); without it the
   results don't update while typing.
2. Register the Stimulus controller: add
   `'sumocoders/SearchModal' => ['path' => './vendor/sumocoders/framework-core-bundle/assets-public/controllers/search_modal_controller.js']`
   to `importmap.php`, and `import SearchModal from 'sumocoders/SearchModal'` +
   `app.register('search-modal', SearchModal)` to `assets/bootstrap.js`.
3. Already built your own Cmd+K search? Remove it, or switch the bundle's off with
   `{% block quick_search %}{% endblock %}`, so the page doesn't get two modals.

## To 19.1

No action needed (dark-mode readability fixes for list group items and contextual table rows, a new
`.alert-icon-centered` modifier, and `.buttons-fixed` now sits under the modal backdrop).

## To 19.0

Two changes are visible in every project on update (both shipped in 19.0.1). Apart from one AssetMapper path for the
font, neither needs code changes, but both are worth a look before you deploy.

**The admin now renders in Lato.** The bundle ships the font itself in `assets/fonts/` and
declares it in `assets/scss/base/_fonts.scss`. Until now `$font-family-sans-serif` named Lato
but nothing loaded it, so every admin fell through to `system-ui`: SF Pro on macOS, Segoe UI
Variable on Windows, Roboto on Android. Rebuild your styles and the whole admin switches to one
typeface. Because Lato's metrics differ from the system fonts, expect small shifts in button
widths, table column fits and line wrapping. Check any screen where text sits close to its
container.

The fonts are served through AssetMapper, so add `vendor/sumocoders/framework-core-bundle/assets/fonts/` to
`framework.asset_mapper.paths` in `config/packages/asset_mapper.yaml` (the skeleton does this since 2026-09-30).
You can skip this if you override `$framework-font-dir`.

If a project already loads its own font, override `$font-family-sans-serif` as before. To serve
the files from somewhere other than the bundle, override `$framework-font-dir`. Both take
effect before the bundle imports, as usual. Mail templates are unaffected on purpose.

**Dark-mode text softened from `#fff` to `#e1e1e1`.** The dark file always meant to do this, but
the declaration was a no-op, so dark text shipped pure white. It now runs through a new
`$white-dark` variable. Contrast against `$body-bg-dark` stays around 8.6:1, well past WCAG AA.
If a project pinned any of `$body-color-dark`, `$body-emphasis-color-dark`,
`$border-color-translucent-dark`, `$form-switch-color-dark`, `$menu-color-dark` or
`$menu-active-color-dark`, those overrides still win.

**`gedmo/doctrine-extensions` is no longer required by the bundle.** The unused `DoctrineExtensionListener` was
removed. If the project uses Gedmo, run `composer require gedmo/doctrine-extensions` so it stays installed.

**Sentry user context.** When `sentry/sentry-symfony` is installed, the new `SentryUserContextListener` attaches the
authenticated user's identifier (and the impersonator's, when impersonating) to Sentry events. There is no config
option. Sentry is only suggested, not required. See [docs/sentry-user-context.md](docs/sentry-user-context.md).

**Uploaded filenames.** `AbstractFile` now slugs the filename prefix with Symfony's `AsciiSlugger`, so newly uploaded
filenames may differ slightly from before. Existing files are untouched.

Smaller, no action needed: `--user-bg` is now declared on `:root` and `[data-bs-theme="dark"]`,
so `.user-nav` gets the background it always referenced. It resolves to the same value as
`--top-color` in both themes, so nothing changes colour. `components/_editorjs.scss` is now
imported, adding dark-mode styles for editor.js that stay inert unless the project loads the
editor. `components/_mark.scss` was removed; Bootstrap's own `--bs-highlight-*` already covers
it. The error and mail SCSS entry points load the bundle variables before Bootstrap. `BelgiumPostCodeType` has its
own service id (it used to overwrite `FileType`'s). `AuditLogger` detects impersonation via `IS_IMPERSONATOR`.

## To 18.0

- The bundle now requires `symfony/validator`, `symfony/http-client` and `symfony/asset-mapper`.
- **`MatchAgainst` DQL function removed.** Use [beberlei/doctrineextensions](https://github.com/beberlei/DoctrineExtensions)
  and register its MATCH function in your Doctrine DQL config.
- **Mail CSS** is now inlined via the new Twig function `asset_content('styles/mail.scss')` instead of reading
  `var/sass/mail.output.css`. Make sure the project's `mail.scss` is available to AssetMapper at `styles/mail.scss`.
- **Typed constants.** `AbstractImage::FALLBACK_IMAGE` is now `?string` and `Paginator::PAGE_SIZE` is `int`.
  Subclasses that override them must declare the same type, otherwise PHP throws a fatal error ("Type of X must be
  compatible...").
- `AbstractImage::getWebPath()` now throws a `RuntimeException` when the file is missing and there's no fallback
  image. It used to return null.
- DBAL type method parameters were renamed to `$column`/`$value` and typed `mixed`. Only relevant for subclasses
  overriding them.
- New: `#[Title]` can be placed on the controller class (for invokable controllers).

## To 17.2

The bundle requires `symfony/ux-autocomplete` ^3.0. Replace `ParentEntityAutocompleteType` with
`BaseEntityAutocompleteType` and update the UX packages in the importmap (`symfony console importmap:update`).

## To 17.1

The "exit impersonation" link now points to the route `user_admin_overview` (was `user_overview`). Make sure that
route exists.

Also new: `#[Breadcrumb]` is allowed on the controller class, and alert icons use Bootstrap Icons.

## To 17.0

- Requires PHP ^8.5, Symfony ^8.0, `doctrine/doctrine-bundle` ^3.2 and `nelmio/security-bundle` ^3.7.
- Bundle commands are now invokable commands. No action unless you extend them.
- Menu item attributes set in `ConfigureMenuEvent` are now merged instead of overwritten.

## To 16.0

**Font Awesome replaced by Bootstrap Icons.** Bundle templates use `bi bi-*`. Update the `icon` value in your menu
items' `labelAttributes` and any custom markup.

The bundle's `style.scss` imports Bootstrap Icons from the project's vendor dir, so run
`composer require twbs/bootstrap-icons` and add `vendor/twbs/bootstrap-icons/font/` to the asset_mapper paths.

## To 15.3

No action needed (fixes for the `header_navigation` empty check and the theme script).

## To 15.2

No action needed (consistent mobile menu breakpoints).

## To 15.1

New optional `confirm` Stimulus controller that shows a confirmation modal before a link or form submit. To use it,
register it like the other bundle controllers:

```
symfony console importmap:require sumocoders/Confirm --path "./vendor/sumocoders/framework-core-bundle/assets-public/controllers/confirm_controller.js"
```

Then add `import Confirm from 'sumocoders/Confirm'` + `app.register('confirm', Confirm)` to `assets/bootstrap.js`.

## To 15.0

Layout and dark-mode rework.

- **`style-dark.scss` removed**, together with `themes/_dark.scss`, `_bootstrap-colors.scss` and `dark/*`. Dark mode
  now uses Bootstrap color modes: `_bootstrap-variables-dark.scss` and `@include color-mode(dark)`. Move project dark
  overrides into the main stylesheet and remove the second stylesheet `<link>`. To turn dark mode off, set
  `$enable-dark-mode: false`.
- New SCSS variables for the menu and top bar: `$top-color`, `$menu-bg`, `$menu-color`, `$menu-active-bg`,
  `$menu-active-color`, plus their `-dark` variants.
- **`symfony/ux-toggle-password` removed.** The bundle ships its own `TogglePasswordTypeExtension`, on by default for
  every password field. Remove the UX package and its importmap/`controllers.json` entry, and register the bundle's
  controller: importmap `sumocoders/TogglePassword` pointing to `controllers/toggle_password_controller.js`, and
  `app.register('toggle-password', TogglePassword)`.
- Navigation is now an offcanvas. The user menu, the theme toggler and the new language toggler live in it.
- The `logo_image` block moved from `navigation.html.twig` to `base.html.twig`, and there are new blocks
  `header_actions_left`, `header_actions_center` and `header_actions_right`. Check template overrides of these.

## To 14.1

- Form labels are hidden with `visually-hidden` instead of `sr-only`. Update custom CSS or tests relying on `sr-only`.
- New optional command `sumo:maintenance:create-pr-for-outdated-dependencies` for GitLab CI. It needs
  `GITLAB_ACCESS_TOKEN` or `SUMO_GITLAB_ACCESS_TOKEN`.
- The audit trail now logs collection changes.

## To 14.0

- Requires Symfony ^7.3. The bundle now requires `symfony/doctrine-messenger`, `symfony/intl` and
  `nelmio/security-bundle`.
- **`friendsofsymfony/jsrouting-bundle` removed.** Remove FOS JS routing usage, and its config and routes, from the
  project.
- **CSP handling moved to NelmioSecurityBundle.** `ResponseSecurer` and the whole `sumo_coders_framework_core` config
  tree (`content_security_policy`, `extra_content_security_policy`, `x_frame_options`, `x_content_type_options`) are
  gone. Delete that config and configure `nelmio_security` instead. The head now outputs a `<meta name="csp-nonce">`
  and the importmap gets a nonce.
- Removed: the `secrets:get` command (use Symfony's `secrets:reveal`), `PasswordStrengthService` (use Symfony's
  `PasswordStrength` constraint) and `PaginatorRuntime` (merged into `PaginatorExtension`). Bundle Twig extensions now
  use `#[AsTwigFunction]`/`#[AsTwigFilter]`, which only matters if you extended them.
- The toast `delay` option was removed from `addToast()`, `_toast.html.twig` and the toast controller. It needed an
  inline style, which CSP blocks.
- **Mail goes through the async Messenger transport.** Mail context must be serializable, and a worker
  (`messenger:consume async`) must run in every environment that sends mail.
- New, optional: `BelgiumPostCodeType`, the `busy-submit` Stimulus controller and `ajaxClient.busy_targets`. See
  [docs/stimulus.md](docs/stimulus.md) and [docs/ajax-client.md](docs/ajax-client.md).

## To 13.0

The user menu links to the route `user_profile` instead of `profile`. Rename the route in the project or override the
`user_navigation` block.

## To 12.2

No action needed (same code as 12.1.6).

## To 12.1

- New AJAX client `assets-public/js/ajax_client.js`, built on axios. To use it:
  ```
  symfony console importmap:require axios
  symfony console importmap:require sumocoders/ajax_client --path "./vendor/sumocoders/framework-core-bundle/assets-public/js/ajax_client.js"
  ```
- `EncryptedDBALType`, `AbstractImageType` and `AbstractFile::setFile()` got stricter signatures for DBAL 4 and PHP
  8.4. Subclasses overriding them must match.
- The audit trail handles money columns.

## To 12.0

Requires PHP ^8.4.

## To 11.2

Requires Symfony ^7.2, `doctrine/orm` ^3.3 (was ^2) and `doctrine/doctrine-bundle` ^2.13. Note that this landed in a
minor release. Projects still on ORM 2 have to migrate first, see the Doctrine ORM 3 upgrade notes.

## To 11.1

No action needed (new `base_no_sidebar.html.twig` layout).

## To 11.0

**Webpack Encore replaced by AssetMapper and Stimulus.**

- The `encore_entry_*` calls are gone. `head.html.twig` loads `asset('styles/style.scss')` (via
  symfonycasts/sass-bundle, so run `sass:build`) and `importmap('app')`.
- The `{% block javascripts %}` block moved from the end of `<body>` in `base.html.twig` into `head.html.twig`. Move
  project overrides of it accordingly.
- The bundle ships its own SCSS in `assets/scss/`. The FrameworkStylePackage npm package is no longer used.
- Bundle images and custom fonts were removed. Templates use `asset('images/...')` instead of `build/images/...`, so
  the project must provide `images/logo-application.svg`, `images/logo-application-dark.svg`,
  `images/logo-application-mail.gif` and favicons.
- Mail CSS is read from `var/sass/mail.output.css` (this changes again in 18.0), so run `sass:build`.
- Legacy JS was replaced by Stimulus controllers in `assets-public/controllers/` (clipboard, date-time-picker,
  form-collection, password-strength-checker, popover, scroll-to-top, sidebar-collapsable, tabs, theme, toast,
  tooltip) and the helpers `js/toast.js` (`sumocoders/addToast`), `js/cookie.js` and `js/debounce.js`. Removed:
  SelectSearch, Popover.js, Tooltip.js, Theme.js, Flash.js, Form.js, and the goback and scrolling JS. Markup using
  `data-role=...` hooks must switch to `data-controller`.
- Add `vendor/sumocoders/framework-core-bundle/assets-public/` to the asset_mapper paths. Each controller is added with
  `symfony console importmap:require sumocoders/<Name> --path "./vendor/sumocoders/framework-core-bundle/assets-public/<path>"`
  and registered in `assets/bootstrap.js` with `app.register('<name>', <Name>)`. See `scripts/assets/js/bootstrap.js`
  in sumocoders/application-skeleton for the full list.
- Turbo is enabled. The sidebar state is kept in a `sidebar_is_open` cookie, read with the new Twig function
  `sidebarIsOpen()`.

## To 10.0

No action needed. 10.0.0 replaced `#[SensitiveData]` with PHP's `#[\SensitiveParameter]` and 10.0.1 reverted that.
Only projects that switched while on exactly 10.0.0 must switch back to `#[SensitiveData]`.

## JS

Sinds versie 5 zijn ale data attrbiuten van Bootstrap ge"namespaced". Dit wil dus zeggen dat ipv data-toggle, het nu data-*bs*-toggle is.

Je kan dit oplossen door een search & replace te doen door heel je project.

```
data-toggle="modal" -> data-bs-toggle="modal"
data-trigger="focus" -> data-bs-trigger="focus"

// etc...
```
## PHP

### Manieren om up te graden
Je gaat best door de oude applicatie en maakt een inschatting van welke staat de code & dependencies zijn.

Afhankelijk van de staat van de codebase kies je voor een update strategy:
* Upgrade in het project zelf (laag aantal code changes, geen major dependency changes)
* Volledige rewrite in nieuw Symfony project (structuur van codebase is volledig anders dan moderne Symfony applicatie, veel code changes, veel dependency conflicten)
* Strangler Fig van de oude applicatie (zie https://symfony.com/doc/current/migration.html#introducing-symfony-to-the-existing-application) (legacy project zonder Symfony langzaam migreren naar Symfony)

### Bundles & Namespaces

Als het project <Symfony 3 is, staat je code mogelijk nog in bundles in src/. Indien dit het geval is begin je best met in PHPStorm de oude src/ folder te renamen naar src_old/, een nieuwe src/ folder te maken, en vervolgens alle code uit de bundles samen te voegen in de nieuwe src/ folder. Normaal gezien zal PHPStorm dan automatisch de juiste namespace al klaarzetten in je files.

### Breadcrumbs
De breadcrumbs in onze core bundle zijn niet BC. Als ze nog op de oude manier werken (met de listener in de controller) dan moet je dit manueel refactoren.

Oude manier:
```php
$this->breadCrumbBuilder->addSimpleItem(
    'order.header.index',
    $this->router->generate(
        'qlab_order_order_overview'
    )
);
```
Nieuwe manier:
```php
    #[Breadcrumb('order.header.index')]
    public function __invoke(
```
Als het enkel annotation -> attributen is, kan je volgende Rector sets gebruiken:

```php
DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
```

### Pagination
Sinds v5.1.0 van de core bundle is paginatie niet meer met Pagerfanta, maar met onze eigen versie. Hiervoor zijn er een aantal changes (manueel) nodig:
* Vervang in de repository alle oude `Pagerfanta` objecten door onze eigen `Paginator` objecten. Beide hebben een QueryBuilder als parameter, zelfde werking.
* Vervang in je template de oude Pagerfanta Twig helper door onze eigen Twig helper.
```twig
    {{ pagination(your_paginated_items) }}
```
* Pas je Controllers aan naar de nieuwe werking:
```php
// getItems returns a Paginator object with a QueryBuilder inside.
$paginatedItems = $itemRepository->getItems();

$paginatedItems->paginate($request->query->getInt('page', 1));
```

### VO -> ENUM
Vroeger gebruikten we veel ValueObjects, ook om simpele string values op te slaan. Sinds PHP8 hebben we hiervoor native enums.

Je kan je VOs dus omzetten naar enums (indien ze enkel een vaste lijst string values kunnen hebben). Zaken zoals Money, Coordinates of Range zijn nog steeds VOs. Zaken zoals Gender, Province, etc.. zijn enums.

Hiervoor is geen tool beschikbaar, Rector kan dit niet. Je moet dus zelf enums gaan maken en overal in de code dit aanpassen. Omdat dit best wel veel werk is, is het niet altijd de moeite om dit te doen. Is een nice-to-have.

In je entities gebruik je `#[ORM\COlumn(enumType: <enum_fqcn>)` van Doctrine. In je Symfony forms gebruik je het `EnumType` form type.

```php
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
}
```
### Annotation -> attributes
Gebruik Rector, met volgende config:
```php
<?php

use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictGetterMethodReturnTypeRector;

return function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_53,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
        SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ParamTypeFromStrictTypedPropertyRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictGetterMethodReturnTypeRector::class,
        LongArrayToShortArrayRector::class,
        TypedPropertyFromAssignsRector::class,
        ReturnTypeFromStrictTypedPropertyRector::class,
        ParamTypeFromStrictTypedPropertyRector::class,
        RemoveUselessParamTagRector::class,
    ]);
};
```

### Security
Pre Symfony 5 was de security.yaml anders. Onder andere de password encoders/hashers config is aangepast.

config/packages/security.yaml
```yaml
    # oud
    encoders:
        App\Entity\User\User:
            algorithm: auto

    # nieuw
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```

Indien er een oude user implementatie (met msgphp bundle, of iets ouder) in het projet zit, is het vaak het snelst om alles weg te smijten en https://github.com/sumocoders/Framework-User-Implementation-Example opnieuw over te nemen.
### Config
De Sentry configuratie om errors te ignoren is aangepast. Je gebruikt nu best deze syntax:

config/packages/sentry.yaml
```yaml
when@prod:
  sentry:
    dsn: '%env(SENTRY_DSN)%'
    options:
      integrations:
        - 'Sentry\Integration\IgnoreErrorsIntegration'
  
services:
  Sentry\Integration\IgnoreErrorsIntegration:
    arguments:
      $options:
        ignore_exceptions:
          - 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'
          - 'Symfony\Component\Security\Core\Exception\AccessDeniedException'

```