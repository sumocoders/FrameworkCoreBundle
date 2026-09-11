# FrameworkCoreBundle Design System

The shared admin theme every SumoCoders project inherits through
`sumocoders/application-skeleton`. Dense, utilitarian, built for data screens: a fixed sidebar
and top bar, a compressed type scale, pill-shaped buttons, flat ambient shadows. Bootstrap's
stock hues ship unchanged so each project sets its own `$primary`.

Source of truth `assets/scss/`, entry point `assets/scss/style.scss`. Bootstrap 5.3 selective
build, overrides in `assets/scss/_bootstrap-variables.scss`. Scope: the bundle's own admin
theme, not a consuming project's separate frontend tree (`assets/styles/frontend.scss` there).

**This bundle does not compile on its own.** `style.scss` imports Bootstrap through
`../../../../../vendor/twbs/bootstrap/`, five levels up, resolving only from inside a consuming
application's vendor tree. `twbs/bootstrap` and `twbs/bootstrap-icons` are not in this bundle's
`composer.json`; the application supplies them.

Everything below is a deliberate deviation from stock Bootstrap 5.3.3. Stock values are not
repeated. Where a partial sets a value directly, that override is what ships and is documented
instead of the variable.

## Colors

**Brand, all stock.** `$primary: $blue` `#0d6efd` (buttons, links, focus, sidebar background),
`$secondary: $gray-600` `#6c757d`, `$dark: $gray-800` `#383a43` (stock points at `$gray-900`).
The bundle ships a neutral base and expects the application to override `$primary`.

`$theme-colors` carries a **ninth, non-stock entry: `"white": $white`**. Every
`@each $color in $theme-colors` loop therefore also emits a `-white` variant, which is where
`.text-bg-white` (used by the sidebar badge), `.btn-white`, `.alert-white` and `.toast-white`
come from. Removing that entry silently deletes those classes.

**Neutrals.** Two grays are darkened; the rest of the ramp is Bootstrap's.
`$body-color: $gray-900` `#2d2f35` (stock `#212529`), `$body-bg: $light` -> `$gray-100`
`#f8f9fa` (stock `$white`), `$border-color: $gray-400` `#ced4da` (stock `$gray-300`),
`$gray-800: #383a43` (stock `#343a40`), plus bundle-only `$gray-850: #2d2f35`.

**Semantic** is stock except `$mark-bg: #fcf8e3` (stock `$yellow-100`) and
`$mark-bg-dark: #420b2a`.

### Application chrome

Sidebar and top bar are themed through bundle-only variables mirrored into CSS custom
properties on `:root` (`_bootstrap-variables.scss:1754-1763`). Components read the custom
properties, never the Sass variables, so a runtime theme swap works. Dark counterparts live in
`_bootstrap-variables-dark.scss:117-135`.

| Sass variable        | Light                | CSS property          | Dark                  |
|----------------------|----------------------|-----------------------|-----------------------|
| `$top-color`         | `$white` `#fff`      | `--top-color`         | `$gray-850` `#2d2f35` |
| `$menu-bg`           | `$primary` `#0d6efd` | `--menu-bg`           | `$gray-850` `#2d2f35` |
| `$menu-color`        | `$white` `#fff`      | `--menu-color`        | `#fff`                |
| `$menu-active-bg`    | `rgba($black, 0.3)`  | `--menu-active-bg`    | `rgba($black, 0.3)`   |
| `$menu-active-color` | `$white` `#fff`      | `--menu-active-color` | `#fff`                |
| `$user-bg`           | `$top-color`         | n/a                   | `$gray-850`           |
| n/a                  | `#b2cadd`            | `--error-bg`          | `#4B6376`             |
| n/a                  | `#ebf3f9`            | `--error-content-bg`  | `#81919F`             |
| n/a                  | `$white`             | `--back-to-top-bg`    | `$gray-700`           |

`$logo-bg` and `$user-bg` default to `$top-color`; `$user-dropdown-bg` to `$dropdown-bg`;
`$navbar-toggler-color` to `var(--bs-body-color)`; `$loading-indicator-color` to `$primary`.

## Typography

**Family.** `$font-family-sans-serif` prepends `"Lato"` to the stock system stack;
`$headings-font-family` is `null`, so headings use the body family.
`assets/scss/base/_fonts.scss` contains only a commented-out `font-import` mixin, so **the
bundle names Lato but never loads it**. Applications must ship the `@font-face` blocks or the
stack falls through to `system-ui`.

**Scale.** Base `$font-size-base: 1rem`, `$line-height-base: 1.5`. Compressed hard for admin
density: Bootstrap's h1 is 2.5x base, this is 1.6x.

| Element | Declared   | Stock     |
|---------|------------|-----------|
| h1      | `1.6rem`   | `2.5rem`  |
| h2      | `1.5rem`   | `2rem`    |
| h3      | `1.25rem`  | `1.75rem` |
| h4      | `1.125rem` | `1.5rem`  |
| h5      | `1rem`     | `1.25rem` |
| h6      | `1rem`     | `1rem`    |

`$enable-rfs: true`, so h1 and h2 ship **fluid**, not flat. Verified against compiled CSS, in
three layers: below `sm` (575.98px) both are `1.3rem` from `base/_type.scss`; from `sm` to `xl`
h1 is `calc(1.285rem + 0.42vw)` and h2 `calc(1.275rem + 0.3vw)`; at `xl` (1200px) and up they
reach the declared `1.6rem` / `1.5rem`. h3 through h6 stay flat, RFS leaves them alone. All
headings render at `font-weight: 700`, `line-height: 1.2`.

The page title in the header bar is smaller still: `.header-title h1 { font-size: 1.1rem }`
(`layouts/_framework.scss:23`). That element rule wins over `$h1-font-size`.

Other overrides: `$headings-font-weight: $font-weight-bold` (700, stock `500`),
`$headings-color: null` (stock `inherit`), `$form-label-font-weight: $font-weight-bold` (700,
stock `null`), `$breadcrumb-font-size: 0.8rem` (stock `null`).

**Links.** `$link-decoration: none`, `$link-hover-decoration: underline`. Undecorated until
hover, the reverse of Bootstrap 5.3.

## Spacing

`$spacer: 1rem` with the stock `0-5` map, and `$gutters: $spacers` as a bundle-only alias. No
custom spacing scale exists; do not invent one. Vertical rhythm is set per layout in
`assets/scss/layouts/`.

## Layout

`$grid-breakpoints` and `$container-max-widths` are stock. The gutter is not:
`$grid-gutter-width: 1.875rem` (30px, stock `1.5rem`) and
`$container-padding-x: $grid-gutter-width * 0.5` (stock the full gutter).

**Shell.** `$sidebar-width-sizer: 5.5rem` is the base unit for both sidebar states:
`$sidebar-width-closed` is `5.5rem` (88px, the collapsed rail) and `$sidebar-width-open` is
`$sidebar-width-sizer * 2.75` = `15.125rem` (242px). `$topbar-height` and `$topbar-height-md`
are both `74px`.

From `lg` up, `.main-wrapper` takes `padding-left: $sidebar-width-open` and `.buttons-fixed` is
width-matched with `calc(100% - $sidebar-width-open)`, narrowing to `$sidebar-width-closed`
under `.sidebar-collapsed`. That state is toggled by the `sidebar-collapsable` Stimulus
controller and persisted in a `sidebar_is_open` cookie.

The shell uses `.container-fluid` with no row/column wrapper. Bootstrap's grid appears only in
`user.html.twig`, `base_error.html.twig`, and the date-picker fallback in the form theme.

**Z-index.** `$zindex-offcanvas: 2105` and `$zindex-offcanvas-backdrop: 2100` (stock `1045` /
`1040`) deliberately lift offcanvas above modals; `$zindex-modal-backdrop: 1040` (stock
`1050`); bundle-only `$zindex-backtotop: 1035`. `.buttons-fixed` sits at `2000`.

## Elevation and shape

| Token            | Variable                            | Value                           | Stock                            |
|------------------|-------------------------------------|---------------------------------|----------------------------------|
| Radius           | `$border-radius`                    | `0.25rem`                       | `0.375rem`                       |
| Radius small     | `$border-radius-sm`                 | `0.2rem`                        | `0.25rem`                        |
| Radius large     | `$border-radius-lg`                 | `0.3rem`                        | `0.5rem`                         |
| Radius buttons   | `$btn-border-radius`                | `30px`                          | `var(--bs-border-radius)`        |
| Shadow           | `$box-shadow`                       | `0 0 0.5rem rgba($black, 0.15)` | `0 .5rem 1rem rgba($black, .15)` |
| Transition       | `$transition-base`                  | `all 0.25s ease-in-out`         | `all .2s ease-in-out`            |
| Theme transition | `$transition-theme-duration`        | `0.25s`                         | bundle-only                      |
| Theme easing     | `$transition-theme-timing-function` | `ease-in-out`                   | bundle-only                      |

Buttons are pills at `30px`; everything else uses the tighter `0.25rem`. The shadow is a flat
ambient glow with no y-offset, not Bootstrap's lifted drop shadow.

**Focus.** The bundle keeps Bootstrap 5.2's focus model rather than 5.3's `$focus-ring-*`
tokens: `$input-btn-focus-width: 0.2rem`, `$input-btn-focus-color: rgba($component-active-bg,
.25)`, `$input-btn-focus-blur: 0`, `$input-focus-border-color: $gray-700` `#495057` (stock
`tint-color($component-active-bg, 50%)`), `$input-focus-box-shadow: 0 0 4px rgba($black, 0.17)`.
Inputs focus to a neutral gray border with a soft black glow, not a primary-colored ring.
`$input-box-shadow: none` removes the stock inset shadow.

Those values are tuned for a light surface, so `components/_forms.scss` inverts them under
`color-mode(dark)`: a `$gray-400` `#ced4da` border with a `rgba($white, 0.25)` glow. Without
that override both the border and the black glow vanish against the `#383a43` dark background,
which fails WCAG 2.4.7. Keep the pair in step when changing either.

## Components

Bootstrap is a **selective** build: `assets/scss/_bootstrap-imports.scss` lists each Bootstrap
partial explicitly, and a component not on that list produces no CSS. Project components live
in `assets/scss/components/` and `assets/scss/layouts/`, imported by `assets/scss/_imports.scss`.
That import list is the component inventory.

**Buttons.** Pill radius `30px`, wide horizontal padding `$input-btn-padding-x: 1.5rem` (stock
`0.75rem`), `$input-btn-padding-x-sm: 1rem` (stock `0.5rem`); vertical padding stays stock at
`0.375rem`. `btn-default` is a **bundle-specific legacy variant**, not Bootstrap 5, and is the
default for chrome controls: theme toggle, language toggle, user menu, modal cancel. In dark
mode every `.btn-{state}` drops to `rgba($value, 0.8)`. `.btn-square` is a 34x34 icon button at
`$card-border-radius`.

**Cards.** `height: 100%`, `overflow: hidden`, `margin-bottom: 0`, with header, body and footer
all painted `$white` in light mode and `$gray-850` in dark. Padding `$card-spacer-x: 1.25rem` /
`$card-spacer-y: 0.75rem` (stock uses `$spacer` for both). `.card-dashboard` is a clickable
tile: `$box-shadow` on hover and `:focus-within`, a stretched `::after` link overlay, a `4rem`
icon, and an `h2` forced to `$font-size-base` (`1.2rem` from `md`). `.card-collection` is the
form-collection container: dashed `1px var(--bs-gray-400)` on `var(--top-color)`.

**Forms.** Theme at `templates/Form/fields.html.twig`, built on `bootstrap_5_layout.html.twig`.
Rows use `.form-group` with `margin-bottom: $spacer`, replacing Bootstrap 5's `mb-3`. Required
fields get an `<abbr>` styled to `var(--bs-primary-text-emphasis)` with no underline; labels are
bold; compound rows render as `<fieldset>` with a `<legend class="col-form-label">`.
`$form-select-disabled-bg: $gray-200`, `$gray-700` in dark mode. Date and time widgets render as
an `input-group` with a `bi bi-calendar-fill` / `bi bi-clock-fill` addon, driven by the
`date-time-picker` Stimulus controller (Flatpickr). Collection widgets nest
`.card.card-collection` > `.card-body` > `ul` > `li.collection-item`, with 40x40 circular
add/remove/drag buttons at `left: -20px` / `right: -20px`, straddling the item edge.

**Tables.** `components/_tables.scss` adds a solid bottom border and bold headers on
`var(--bs-body-bg)`. Cell padding is bumped to `0.75rem` (stock `0.5rem`).
`$table-striped-bg` is a 5% tint of `--bs-emphasis-color-rgb`, so stripes adapt to the active
theme. `$table-active-bg-factor` is `0.75` against a stock `0.1`, noted in open questions.

Bootstrap 5.3 paints striped, hover and active rows with
`box-shadow: inset 0 0 0 9999px var(--bs-table-bg-state, var(--bs-table-bg-type, ...))`. Do not
reset `box-shadow` on `.table` cells: that switch is the whole row-state mechanism, and
clearing it silently disables striping, hover and active together. Only the `thead` cells reset
it, so the header stays flat. The bundle renders no table markup itself; `docs/crud.md` prescribes
`<table class="table">` with `<th class="text-end">` action columns.

**Alerts and toasts.** Flash messages render as **toasts, not alerts**:
`templates/notifications.html.twig` maps flashbag keys `success` -> `success`,
`report` -> `info`, `warning` -> `danger` with `autohide: false`. Toasts are
`$toast-max-width: 380px` (stock `350px`) and fully opaque `var(--bs-body-bg)` (stock is 85%
translucent); each `.toast-{state}` gets a `6px` left border, a `32px` circular
`.toast-icon-wrapper`, and a `5px` progress bar, positioned bottom-right on mobile and
top-right from `md`. `.alert-*` is still styled in `components/_alerts.scss` (Bootstrap Icons
glyph via `::before`, `padding-left: $spacer * 2.5`) but **nothing in the bundle renders it**.
It exists for application use.

**Pagination.** `templates/Twig/pagination.html.twig` plus `components/_pagination.scss`. Page
links are `min-height: 34px`, `margin: 2px`, bordered `var(--bs-border-color)`, flipping to
`var(--bs-primary)` with black text on hover/focus/active. `$pagination-color` and
`$pagination-hover-color` both point at `var(--bs-body-color)`, not the link color.

**Empty states.** `.data-no-results` (`components/_no-results.scss`): centered column,
`1.125rem`, `var(--bs-gray-600)`, `140px` illustration. See `docs/no-results.md`.
`.no-items-icons` scales `2rem` -> `4rem` (`sm`) -> `7rem` (`lg`).

### Full partial inventory

Every entry in `assets/scss/_imports.scss`, in import order. Those not detailed above carry
small adjustments only.

**base/**: `fonts` (commented-out mixin, loads nothing), `images` (`img { max-width: 100% }`),
`type` (h1/h2 to `1.3rem` below `sm`). `no-sidebar` is imported **last** as an override layer;
keep it there.

**components/**: `accordion` (dark-mode `.accordion-button` only), `alerts` (icon glyph via
`::before`, dark-mode recoloring), `autocomplete` (jQuery UI `.ui-autocomplete` menu skin),
`back-to-top` (floating `.back-to-top` button), `buttons` (dark-mode `rgba($value, 0.8)` per theme color),
`cards` (card shell, `.card-dashboard`, `.card-collection`, `.btn-square`), `datagrids`
(`td.action` column widths), `datepicker` (Flatpickr skin), `dropdowns` (`.dropdown-header`
only), `empty-state` (`.no-items-icons` sizing), `forms` (`.form-group`, required `abbr`, widget
heights), `form-collection` (`.collection-item` and its circular buttons), `list-group`
(dark-mode `.list-group-item` only), `loading` (`.loading-indicator`), `modal` (adjustments),
`navbar` (sidebar and top bar, largest partial at 522 lines), `navs` (`.nav-tabs` only),
`offcanvas` (mobile nav and user panel), `page-header` (`.page-header` only), `pagination` (link
sizing and hover), `scrolling` (`scroll-margin` for anchors and tab panes), `tables` (header
weight, border handling), `toasts` (variants, icon wrapper, progress bar), `no-results`
(`.data-no-results`), `password-strength-meter` (`.meter-section`,
weak/medium/strong/very-strong), `toggle-password` (`.toggle-password-*` control), `icons`
(`.bi` and `.menu-item-icon` sizing).

**layouts/**: `framework` (header title, `.buttons-fixed`, `.actions`, `.main-content`),
`search` (`.search-box` in the top bar), `header` (`.sub-nav`, `.main-header`, sidebar offset
from `lg`), `actions` (`.action-buttons` spacing).

**plugins/**: `tom-select`, `quill` (editor skins).

Authored but **not imported**, so producing no CSS: `components/_editorjs.scss`,
`components/_mark.scss`, `plugins/_bootstrap-tagsinput.scss`.

### Layouts

| Template                              | Body class                               | Use                                                     |
|---------------------------------------|------------------------------------------|---------------------------------------------------------|
| `templates/base.html.twig`            | `body-base`                              | Standard admin page: top bar, sidebar, fixed action bar |
| `templates/base_no_sidebar.html.twig` | `body-base` + `.main-wrapper-no-sidebar` | Same without the sidebar                                |
| `templates/user.html.twig`            | n/a                                      | Auth screens: centered card                             |
| `templates/base_error.html.twig`      | `error-page`                             | Error pages, loads `error.scss`                         |
| `templates/empty.html.twig`           | `body-empty`                             | Bare container, no chrome                               |
| `templates/Mail/base.html.twig`       | n/a                                      | Inky/Foundation email layout, inlines `mail.scss`       |

### Page composition

Everything inside `{% block main %}` sits in a card. Nothing rests directly on the page
background.

- One section -> one card.
- A page with several forms or sections -> **one card per section**, never a single card
  wrapping them all.
- An overview -> **one card per item**, not one card around the whole list.
- A table -> `.card` > `.card-body` like anything else. Keep the `card-body` rather than
  letting the table run edge to edge against the card: a list section usually carries a
  title or intro text alongside the table, and `card-body` gives that room.

Cards are always separated. Stacked cards carry `mb-3`; cards laid out in columns drop `mb-3`
and take `gy-3` on the containing `.row` instead, so the gutter does the spacing.

```twig
{% block main %}
    {# stacked sections #}
    <div class="card mb-3">
        <div class="card-body">{# first section #}</div>
    </div>
    <div class="card mb-3">
        <div class="card-body">{# second section #}</div>
    </div>

    {# or, in columns: gy-3 on the row, no mb-3 on the cards #}
    <div class="row gy-3">
        {% for item in items %}
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">{# one item #}</div>
                </div>
            </div>
        {% endfor %}
    </div>
{% endblock %}
```

This is the standard for new templates. Existing pages in consuming projects predate it and
put form rows straight into `{% block main %}`, so treat non-carded pages as unconverted, not
as counter-examples.

Note the padding this stacks with: `.main-content` already supplies `padding: $spacer 0`
(plus `padding-bottom: 100px` under `.has-buttons-fixed`), and each card adds
`$card-spacer-x: 1.25rem` / `$card-spacer-y: 0.75rem` of its own.

## Icons

Bootstrap Icons only, no Font Awesome. Referenced as `<i class="bi bi-name"></i>` and sized in
`components/_icons.scss`: `.bi { font-size: 1.1em }`, `.menu-item-icon { font-size: 1.2em }`.
Alerts use the font as `::before` content (`\f33a`, `\f633`, `\f430`). One exception:
`assets-public/controllers/toggle_password_controller.js` inlines two SVGs.

## Dark mode

Bootstrap 5.3 color modes, `$enable-dark-mode: true`. `templates/settheme.html.twig` reads
`localStorage.theme` and sets `data-bs-theme` on `<html>` before paint;
`assets-public/controllers/theme_controller.js` writes it back and follows
`prefers-color-scheme` on `auto`; `templates/themetoggler.html.twig` renders the
light / dark / auto dropdown.

Tokens live in `assets/scss/_bootstrap-variables-dark.scss`: `$body-bg-dark: $gray-800`
`#383a43`, `$body-color-dark: $white` -> `#fff` (see open questions),
`$border-color-dark: $gray-600` `#6c757d`, and `$menu-bg-dark` / `$top-color-dark` /
`$user-bg-dark` all `$gray-850` `#2d2f35`.

Component-level dark styles use `@include color-mode(dark) { ... }`, one block at the end of the
relevant file. The mixin cannot be nested.

## Motion

`$transition-base: all 0.25s ease-in-out`. Chrome that recolors on theme switch uses
`$transition-theme-duration` (`0.25s`) and `$transition-theme-timing-function` (`ease-in-out`)
so the sidebar and header cross-fade rather than snap. `$enable-reduced-motion: true` (stock),
so Bootstrap's `prefers-reduced-motion` guards apply. Toast progress bars animate via a local
`@keyframes progress` with a `0.5s` delay.

## Accessibility

- `$min-contrast-ratio` is stock `4.5` (WCAG AA).
- Focus is never removed, only recolored to a neutral gray border plus a soft glow. Verify
  contrast when overriding `$input-focus-border-color`.
- Breadcrumbs carry schema.org `itemprop` markup; multi-part date widgets give each `<select>` a
  `visually-hidden` label; toasts get `role="alert"` or `role="status"` by autohide.

## Codebase conventions

**Assets.** Symfony AssetMapper. No webpack, no npm build, no `package.json`. SCSS is compiled
by `symfonycasts/sass-bundle` **in the consuming application**, not here. JavaScript ships as
Stimulus controllers in `assets-public/controllers/` and helpers in `assets-public/js/`,
imported by the application's own `assets/bootstrap.js`.

**Build.** There is none in this repo. From a consuming project:
`symfony console sass:build --watch` to develop, `symfony console sass:build` for a one-off,
`symfony console asset-map:compile` for production.

**Import order is load-bearing.**

```
vendor/twbs/bootstrap/scss/functions    required before any override
bootstrap-variables                     all light-mode overrides
bootstrap-variables-dark                all dark-mode overrides
vendor/twbs/bootstrap/scss/variables    consumes the overrides
vendor/twbs/bootstrap/scss/variables-dark
bootstrap-imports                       the selective Bootstrap build
vendor/twbs/bootstrap-icons/font/...    icon font
imports                                 base, components, layouts, plugins
```

A token defined after the Bootstrap `variables` import is invisible to Bootstrap. All three
entry points follow this order: `style.scss`, `error.scss`, `mail.scss`. The latter two had it
inverted until recently, which silently made every override in this document inert for error
pages and emails; if you add a fourth entry point, copy the order from `style.scss`.
`mail.scss` omits `bootstrap-variables-dark` on purpose, since Foundation for Emails has no
color modes; `error.scss` includes it because `templates/base_error.html.twig` pulls in
`settheme.html.twig` and therefore gets `data-bs-theme`.

**Where a change goes.**

| Change                       | File                                                                           |
|------------------------------|--------------------------------------------------------------------------------|
| Light-mode token             | `assets/scss/_bootstrap-variables.scss`                                        |
| Dark-mode token              | `assets/scss/_bootstrap-variables-dark.scss`                                   |
| Enable a Bootstrap component | add a line to `assets/scss/_bootstrap-imports.scss`                            |
| New component style          | `assets/scss/components/_<name>.scss`, then add to `assets/scss/_imports.scss` |
| Layout / shell               | `assets/scss/layouts/`                                                         |
| Interactive behaviour        | a Stimulus controller in `assets-public/controllers/`                          |
| Email styling                | `assets/scss/mail.scss` (Inky + Foundation for Emails)                         |

**Partials must be underscore-prefixed** and reached through an `@import`. A partial not listed
in `_imports.scss` produces no CSS.

**Code quality.** `./vendor/bin/twig-cs-fixer lint templates/` and
`npx stylelint "assets/**/*.scss"`. `.stylelintignore` excludes `assets/scss/foundation-emails/`
(vendored Foundation for Emails, not part of this design system).

## Guidelines

Do:

- Set `$primary` and the `$menu-*` / `$top-color` pairs to rebrand. That covers most of it.
- Read chrome colors through the CSS custom properties (`var(--menu-bg)`, `var(--top-color)`),
  not the Sass variables, so runtime theme switching works.
- Put dark-mode styles in a single `@include color-mode(dark)` block at the end of the file.
- Base new components on a Bootstrap component before writing one from scratch.
- Keep `base/_no-sidebar.scss` last in `_imports.scss`.
- Wrap every `{% block main %}` section in `.card` > `.card-body`, one card per section or
  per overview item, separated with `mb-3` or a `gy-3` row.

Do not:

- Restate `$primary` per component. Setting it recolors buttons, links, focus and the sidebar
  at once.
- Add a Bootstrap component's styles by hand; add its import to `_bootstrap-imports.scss`.
- Nest `color-mode()`.
- Assume Lato is loaded. It is named but not shipped.
- Put content straight into `{% block main %}` with no card around it.
- Wrap several unrelated sections in one shared card, or let two cards touch with no
  `mb-3` / `gy-3` between them.
- Edit compiled CSS in the application's `public/assets/`.

## Open questions

- TODO: confirm Lato. `$font-family-sans-serif` names it but `assets/scss/base/_fonts.scss` is
  entirely commented out, so the bundle never loads the font. Should the bundle ship the
  `@font-face` blocks, or is that the application's job?
- TODO: `$white: #e1e1e1` in `_bootstrap-variables-dark.scss:15` is dead code. `$white` is
  already bound to `#fff` by `_bootstrap-variables.scss:14` and `!default` makes the later
  declaration a no-op. Confirmed by compiling: `#e1e1e1` appears zero times in the 434KB output,
  and dark mode resolves `--bs-body-color` and `--menu-color` to `#fff`. Dark text is therefore
  pure white rather than the softer off-white intended.
- TODO: `$gray-850` and `$gray-900` are both `#2d2f35`. Is the duplication intentional?
- TODO: `$table-hover-bg` and `$table-active-bg` use `rgba($black, ...)` where stock uses
  `rgba(var(--#{$prefix}emphasis-color-rgb), ...)`, so both darken in dark mode instead of
  lightening; hover is near-invisible on a dark row. `$table-active-bg-factor` is also `0.75`
  against a stock `0.1`, next to a hover factor of `0.075`, which looks like a dropped zero.
  These were unobservable while the row-state box-shadow was reset. Now that striping and hover
  render, both values show, and `0.75` reads as a near-black overlay. Confirm the intent.
- TODO: `layouts/_header.scss:22` sets `.user-nav { background-color: var(--user-bg) }`, but
  `--user-bg` is never declared in any `:root` or `[data-bs-theme]` block. The Sass `$user-bg` /
  `$user-bg-dark` variables exist but are only read through `color-contrast()` and
  `shade-color()`. `.user-nav` currently gets no background.
- TODO: `components/_mark.scss` computes `color-contrast($mark-bg)` inside a dark-mode block,
  reading the light `#fcf8e3` rather than `$mark-bg-dark` `#420b2a`. Moot while the file stays
  unimported, but wrong if it is ever added to `_imports.scss`.
- TODO: `$sidebar-width-sizer: 5.5rem` is declared twice on consecutive lines
  (`_bootstrap-variables.scss:1731-1732`).
- TODO: `$font-family-base` and `$font-family-code` interpolate `$variable-prefix`, the name
  Bootstrap deprecated in favour of `$prefix`. Not broken: the fork defines
  `$variable-prefix: bs-` at `_bootstrap-variables.scss:392` with `$prefix: $variable-prefix`
  on the next line, and the compiled CSS emits `var(--bs-font-sans-serif)` correctly. Worth
  renaming before a Bootstrap version drops the alias.
- TODO: the header comment in `_bootstrap-imports.scss` still says `Bootstrap v5.0.2`; the
  codebase targets 5.3.
- TODO: `components/_editorjs.scss` and `components/_mark.scss` exist but are not in
  `_imports.scss`, so they produce no CSS. Dead files, or a missing import?
- TODO: `twbs/bootstrap` and `twbs/bootstrap-icons` are undeclared peer dependencies. Should
  they be added to `composer.json`?
