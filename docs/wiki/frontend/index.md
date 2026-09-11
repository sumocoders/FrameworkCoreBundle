[Back to index](../index.md)

# Frontend conventions

JS/SCSS conventions and pre-built components shipped by the bundle's frontend assets (Symfony AssetMapper +
Bootstrap 5 + Stimulus). This page indexes what exists; the linked `docs/*.md` files are the user-facing how-to —
read those for setup and options, this page is just the map.

## AJAX client

Pre-configured Axios wrapper (`assets-public/js/ajax_client.js`) with a 2500ms default timeout, CSRF-token payload
injection, automatic success/error toasts driven by a `message` key in the JSON response, and busy-button spinner
support via `busy_targets`. See [../../ajax-client.md](../../ajax-client.md).

## Asset pipeline

Symfony AssetMapper manages both JS and CSS; the bundle's own Sass lives under `assets/scss/` in the consuming app
and is compiled via `symfony console sass:build`. Packages are added with `importmap:require`, compiled for
production with `asset-map:compile`. See [../../asset-mapper.md](../../asset-mapper.md) and
[../../frontend-development.md](../../frontend-development.md) for the SCSS variable/component folder conventions
(`_bootstrap-variables.scss`, `components/`, dark-mode's `@include color-mode(dark) { ... }` block).

## Dark mode

Built on Bootstrap 5.3 color modes. Theme choice lives in `localStorage.theme`, applied as `data-bs-theme` on
`<html>` by `templates/settheme.html.twig` before render (no server-side cookie involved); the Stimulus `theme`
controller flips it on toggle. Dark-only variable overrides go in `assets/scss/_bootstrap-variables-dark.scss`. See
[../../dark-mode.md](../../dark-mode.md).

## Stimulus controllers

Pre-built controllers registered automatically once imported in `assets/controllers.json`/`bootstrap.js`: clipboard,
toast, tooltip, popover, tabs (with URL-anchor history push), password strength/visibility toggle, date/time
pickers, sidebar, form collection, scroll-to-top, busy-submit button, and a confirm-modal controller for
delete/navigation confirmations. See [../../stimulus.md](../../stimulus.md).

## No-results component

Standard empty-state markup (`.data-no-results` + illustration) for lists/datagrids with zero rows, with an
optional "reset filters" link variant. Decorative image uses `alt=""`. See [../../no-results.md](../../no-results.md).

## Language switch

Locale switcher driven by the `{_locale}` route parameter/prefix and a `locales` parameter (passed to Twig) rendered
as a navbar dropdown snippet. See [../../language-switch.md](../../language-switch.md).
