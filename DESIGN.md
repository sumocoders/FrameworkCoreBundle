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
`composer.json`; the application supplies them. See Peer dependencies.

Everything below is a deliberate deviation from stock Bootstrap 5. Stock values are not
repeated. Where a partial sets a value directly, that override is what ships and is documented
instead of the variable.

## Colors

**Brand, all stock.** `$primary: $blue` `#0d6efd` (buttons, links, focus, sidebar background),
`$secondary: $gray-600` `#6c757d`, `$dark: $gray-800` `#383a43` (stock points at `$gray-900`).
The bundle ships a neutral base and expects the application to override `$primary`.

`$theme-colors` carries a **ninth, non-stock entry: `"white": $white`**. Every
`@each $color in $theme-colors` loop therefore also emits a `-white` variant: `.text-bg-white`,
`.btn-white`, `.btn-outline-white`, `.alert-white`, `.toast-white`, `.link-white`,
`.link-underline-white`, `.list-group-item-white` and `.focus-ring-white`, about 4KB of the
compiled stylesheet.

The bundle itself uses exactly one of them, `.text-bg-white` on the sidebar badge in
`templates/Menu/menu.html.twig`. The rest exist for applications. Removing the entry silently
deletes all of them, so swap the badge to `bg-white text-dark` first if you ever drop it.

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
| `$menu-color`        | `$white` `#fff`      | `--menu-color`        | `#e1e1e1`             |
| `$menu-active-bg`    | `rgba($black, 0.3)`  | `--menu-active-bg`    | `rgba($black, 0.3)`   |
| `$menu-active-color` | `$white` `#fff`      | `--menu-active-color` | `#e1e1e1`             |
| `$user-bg`           | `$top-color`         | `--user-bg`           | `$gray-850` `#2d2f35` |
| n/a                  | `#b2cadd`            | `--error-bg`          | `#4B6376`             |
| n/a                  | `#ebf3f9`            | `--error-content-bg`  | `#81919F`             |
| n/a                  | `$white`             | `--back-to-top-bg`    | `$gray-700`           |

`$logo-bg` and `$user-bg` default to `$top-color`; `$user-dropdown-bg` to `$dropdown-bg`;
`$navbar-toggler-color` to `var(--bs-body-color)`; `$loading-indicator-color` to `$primary`.

## Typography

**Family.** `$font-family-sans-serif` prepends `"Lato"` to the stock system stack;
`$headings-font-family` is `null`, so headings use the body family.

The bundle ships Lato itself. `assets/fonts/` holds the woff2 and woff files and the SIL Open
Font License, and `assets/scss/base/_fonts.scss` declares them through a `font-import` mixin.
Self-hosted on purpose: no Google Fonts request, so no third-party call from an admin page and
nothing to allow in the CSP that `nelmio/security-bundle` manages.

Six faces are declared, the weights the bundle actually uses:

| Weight | Upright           | Italic                  | Used by                                  |
|--------|-------------------|-------------------------|------------------------------------------|
| 300    | `Lato-Light`      | `Lato-LightItalic`      | `$display-font-weight`, `$lead-font-weight` |
| 400    | `Lato-Regular`    | `Lato-Italic`           | `$font-weight-base`                      |
| 700    | `Lato-Bold`       | `Lato-BoldItalic`       | `$headings-font-weight`                  |

`$font-weight-semibold: 600` is used by `layouts/_header.scss` and `layouts/_framework.scss`,
but Lato ships no 600 weight, so the browser synthesises it toward Bold. `Lato-Black` (900) and
`Lato-Hairline` (100) sit in `assets/fonts/` but are deliberately **not** declared. Nothing
references them, and an undeclared face is never downloaded. An application that wants one adds
its own `@font-face` pointing at the bundle file.

`$framework-font-dir` holds the path, defaulting to
`../../vendor/sumocoders/framework-core-bundle/assets/fonts`, resolved relative to the
application's compiled CSS. It carries `!default`, so an application that serves the files from
elsewhere can override it before importing the bundle. `font-display: swap` on every face:
text paints immediately in the fallback and reflows when Lato lands.

`style.scss` and `error.scss` both import the fonts. `mail.scss` does not, on purpose. Mail
clients do not load webfonts, so the `@font-face` blocks would be dead weight in inlined CSS.

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

`overflow: hidden` clips anything positioned outside the card: an autocomplete (Tom Select) list,
a `.dropdown-menu` or a popover gets cut off at the card edge. A card that holds one takes
`.overflow-visible`; every other card keeps the default. See `docs/card-layouts.md`.

**Forms.** Theme at `templates/Form/fields.html.twig`, built on `bootstrap_5_layout.html.twig`.
Rows use `.form-group` with `margin-bottom: $spacer`, replacing Bootstrap 5's `mb-3`. Required
fields get an `<abbr>` styled to `var(--bs-primary-text-emphasis)` with no underline; labels are
bold; compound rows render as `<fieldset>` with a `<legend class="col-form-label">`.
`$form-select-disabled-bg: $gray-200`, `$gray-700` in dark mode. Date and time widgets render as
an `input-group` with a `bi bi-calendar-fill` / `bi bi-clock-fill` addon, driven by the
`date-time-picker` Stimulus controller (Flatpickr). Collection widgets nest
`.card.card-collection` > `.card-body` > `ul` > `li.collection-item`, with 40x40 circular
add/remove/drag buttons at `left: -20px` / `right: -20px`, straddling the item edge.

**Form layout.** A form with more than a handful of fields splits into titled section cards
(see Page composition), with one `form_start()` / `form_end()` around all of them so the
toolbar submit button still sends everything. Within a card:

- Order fields by task flow. A field that other fields depend on comes first: the input a
  lookup searches on, a type selector that changes the rest of the form.
- Short related fields share a `.row` with `col-md-*` columns; long text fields and textareas
  take the full width. Two collection widgets side by side need `.gx-5` on the row, because
  their buttons stick out 20px on both sides and overlap in the default gutter.
- An action that works on one field (look up, generate, copy) attaches to it in an
  `.input-group`, with a `.form-text` below saying what it does. `form_errors()` goes below the
  input group, not inside it.
- A field that is alone in a titled card drops its visible label (`label: false`) and keeps an
  `aria-label`, so the card title is not repeated.
- When create and update share a custom layout, put it in a `_form.html.twig` partial that both
  include. `docs/card-layouts.md` has a complete example.

**Tables.** `components/_tables.scss` adds a solid bottom border and bold headers on
`var(--bs-body-bg)`. Cell padding is bumped to `0.75rem` (stock `0.5rem`).
Row states are stock: striped, hover and active are `5%`, `7.5%` and `10%` tints of
`--bs-emphasis-color-rgb`, so all three adapt to the active theme and stay in that order of
prominence.

Bootstrap 5.3 paints striped, hover and active rows with
`box-shadow: inset 0 0 0 9999px var(--bs-table-bg-state, var(--bs-table-bg-type, ...))`. Do not
reset `box-shadow` on `.table` cells: that switch is the whole row-state mechanism, and
clearing it silently disables striping, hover and active together. Only the `thead` cells reset
it, so the header stays flat.

The bundle renders no table markup itself; `docs/crud.md` prescribes
`<table class="table">` with `<th class="text-end">` action columns. `.table-responsive` goes
on a wrapper `<div>` around the table, never on the `<table>`.

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
`.no-items-icons` scales `2rem` -> `4rem` (`sm`) -> `7rem` (`lg`). Use it when the page's main
list is empty. An empty section inside a larger page (the notes or contacts of a detail page)
gets one `<p class="text-body-secondary mb-0">` line instead, so it does not take more room
than the section would with content in it.

### Full partial inventory

Every entry in `assets/scss/_imports.scss`, in import order. Those not detailed above carry
small adjustments only.

**base/**: `fonts` (commented-out mixin, loads nothing), `images` (`img { max-width: 100% }`),
`type` (h1/h2 to `1.3rem` below `sm`). `no-sidebar` is imported **last** as an override layer;
keep it there.

**components/**: `accordion` (dark-mode `.accordion-button` only), `alerts` (icon glyph via
`::before`, dark-mode recoloring), `autocomplete` (jQuery UI `.ui-autocomplete` menu skin),
`back-to-top` (floating `.back-to-top` button), `buttons` (dark-mode `rgba($value, 0.8)` per
theme color),
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

`components/_editorjs.scss` is a dark-mode patch for editor.js, all `.ce-*` and `.cdx-*`
selectors. It compiles for every application but matches nothing unless that application loads
editor.js, so applications that use the editor get the dark theme without copying it locally.

Authored but **not imported**, so producing no CSS: `plugins/_bootstrap-tagsinput.scss`.

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
- Related items on a detail page -> **one card holding a responsive list**, not a grid of item
  cards. See Detail pages below.
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

A section with a title puts it in `.card-header`, not as a heading inside `.card-body`.

**Overview pages** follow one order: a filter card, a result summary (count, plus a reset link
while a filter is active), the results, then `{{ pagination() }}`. In the filter card, a widget
rendered without a label gets a `placeholder` and an `aria-label`, and there is one primary
button; secondary actions such as export are `btn-outline-secondary`, pushed right. "Nothing yet"
and "no matches for this filter" are separate empty states, and only the second gets a reset link.

**Item cards** in an overview grid:

- Title as `<h2 class="h5 card-title">`, since the page `<h1>` lives in the header bar. When the
  item has a detail page, the title is a `link-body-emphasis` link to it.
- Status badges sit next to the title, not on their own line.
- Show a field only when it has a value; never a label with nothing after it. Prefer a
  `list-unstyled` list with one Bootstrap Icon per field over "Label: value" rows, and
  `.text-truncate` on long values.
- Actions go in `.card-footer.d-flex.gap-2` as `btn-sm` buttons. Which actions an item has depends
  on the entity; leave the footer out when there are none. An icon-only button carries `title`
  and `data-controller="tooltip"` on the button itself plus `.visually-hidden` text.
- Cards in a row stretch to the tallest one. When items can have little content, give the card
  body a `min-height` through a project class so a sparse row does not collapse to title height.

**Detail pages** keep the record compact, because its related lists can grow long:

- Two columns from `xl`: a main column (`col-xl-9`) with the record's own fields and its related
  lists, and a side column (`col-xl-3`) for secondary information such as history. Below `xl` the
  side column stacks under the main one. Put `align-items-start` on the `.row` (see Template
  traps).
- The record's fields go in a `dl.row`, label and value side by side, and only the fields that
  have a value.
- Each related collection gets its own titled card: a count badge next to the title, and its
  "add" action as a `btn-sm` in the same header. The list itself is a responsive list: a
  `list-group-flush` whose items are grid rows. From `md` up the columns line up under a header
  row and read as a table; below `md` each item stacks into its own block and empty fields drop
  out. Bootstrap's grid, order and display utilities do this without custom CSS.

`docs/card-layouts.md` has complete, copyable examples of section cards, an overview page, an
item card and a detail page.

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
`#383a43`, `$body-color-dark: $white-dark` `#e1e1e1`, `$border-color-dark: $gray-600`
`#6c757d`, and `$menu-bg-dark` / `$top-color-dark` / `$user-bg-dark` all `$gray-850` `#2d2f35`.

**`$white-dark`, not `$white`.** Dark text is a soft `#e1e1e1`, not pure white, at roughly 8.6:1
against `$body-bg-dark`. It needs its own variable because `$white` is a single global Sass
variable, not a per-theme one: it also feeds `$theme-colors`, `$color-contrast-light` and
`$top-color`, the light header background. Rebinding `$white` inside the dark file would change
light mode too. Six dark tokens read `$white-dark`: `$body-color-dark`,
`$body-emphasis-color-dark`, `$border-color-translucent-dark`, `$form-switch-color-dark`,
`$menu-color-dark` and `$menu-active-color-dark`. `$mark-color-dark` follows through
`$body-color-dark`.

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
- Put a section title in `.card-header`.
- Add `.overflow-visible` to a card that holds an autocomplete, dropdown or popover.
- Give every icon-only button an accessible name and put its tooltip on the button.

Do not:

- Restate `$primary` per component. Setting it recolors buttons, links, focus and the sidebar
  at once.
- Add a Bootstrap component's styles by hand; add its import to `_bootstrap-imports.scss`.
- Nest `color-mode()`.
- Load Lato from Google Fonts or another CDN. The bundle ships and declares it, and
  `$framework-font-dir` points an application at another copy.
- Put content straight into `{% block main %}` with no card around it.
- Wrap several unrelated sections in one shared card, or let two cards touch with no
  `mb-3` / `gy-3` between them.
- Render a field label with no value after it, in a card or anywhere else.
- Put `.table-responsive` on the `<table>` itself; it only works on a wrapper.
- Edit compiled CSS in the application's `public/assets/`.

## Known quirks

Things that look like bugs but are deliberate. Each was checked against compiled output.

- **`$gray-850` and `$gray-900` are both `#2d2f35`.** Not a copy-paste slip. `$gray-850` is a
  named hook for the application chrome (`$top-color-dark`, `$menu-bg-dark`, `$user-bg-dark`)
  so a project can darken the sidebar without moving `$body-color`. Keep both names even while
  the values match.
- **`$font-family-base` and `$font-family-code` interpolate `$variable-prefix`**, the name
  Bootstrap deprecated in favour of `$prefix`. The fork defines `$variable-prefix: bs-` at
  `_bootstrap-variables.scss:392` with `$prefix: $variable-prefix` on the next line, and the
  compiled CSS emits `var(--bs-font-sans-serif)` correctly. Fork-internal, so renaming buys
  nothing until the fork is rebased on a Bootstrap that drops the alias.
- **`mark` has no bundle partial.** Bootstrap 5.3 already emits `--bs-highlight-color` and
  `--bs-highlight-bg` per theme, resolving to `#e1e1e1` on `#420b2a` in dark. A bundle override
  could only duplicate or regress that.
- **The focus model is Bootstrap 5.2's**, not 5.3's `$focus-ring-*` tokens. See
  Elevation and shape. Deliberate, and revisiting it is its own piece of work.

## Template traps

Mistakes that render without an error but break behavior.

- **`form_widget(field, {attr: {...}})` replaces the form type's `attr`.** Stimulus
  `data-controller`, `data-action` and `data-*-target` attributes set in the form type are gone.
  Merge instead: `form_widget(field, {attr: field.vars.attr|merge({class: 'btn-outline-primary'})})`.
- **Display utilities are `!important`.** `.d-flex`, `.d-block` and friends override any
  `display: none` that CSS uses to show or hide an element. Put the utility on an inner element.
- **Collection widgets overlap side by side.** Their buttons stick out 20px past each item, more
  than half the default `1.875rem` gutter. Use `.gx-5` on the row (see Form layout).
- **Stacked cards in a column disappear.** `.card` has `height: 100%`, and a `.col` in a `.row`
  stretches to the height of the tallest column. The first card then fills its whole column and
  pushes the cards below it out of view. Put `align-items-start` on the `.row`, so each column
  keeps the height of its own content.

## Peer dependencies

`style.scss` imports Bootstrap and Bootstrap Icons straight out of `vendor/twbs/`, five levels
up, but neither package is in this bundle's `composer.json`. That is deliberate: the application
picks the Bootstrap version, and a `require` here would let the bundle pin a major against the
project's wishes. `sumocoders/application-skeleton` supplies them instead.

Both imports are hard `@import`s, so a missing package fails the SCSS build with a
file-not-found, not a degraded page.

| Package                | Declared in the skeleton |
|------------------------|--------------------------|
| `twbs/bootstrap`       | `^5.3`                   |
| `twbs/bootstrap-icons` | `^1.13`                  |

Both are on skeleton `master`, so a project scaffolded from it builds without extra steps. If
you ever see `style.scss` fail on a missing `vendor/twbs/` path, the project predates one of
those entries; add it there rather than here.
