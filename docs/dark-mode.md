# Dark mode

The bundle uses Bootstrap 5.3 color themes for dark mode. The active theme is stored in `localStorage` (`theme`) and applied
via the `data-bs-theme` attribute on `<html>` (see `templates/settheme.html.twig`).
No server-side cookie is required for the Bootstrap theme switcher to work.

Color variables are defined in `assets/scss/_bootstrap-variables-dark.scss`. See
the [Bootstrap color modes documentation](https://getbootstrap.com/docs/5.3/customize/color-modes/) for all available
CSS variables.

## How it works

1. `templates/settheme.html.twig` reads `localStorage.theme` and sets `data-bs-theme` before the page renders
2. The Stimulus `theme` controller updates `localStorage.theme` and `data-bs-theme` when toggled
3. `templates/themetoggler.html.twig` renders the theme toggle UI

## Customize variables

Override Bootstrap dark-mode CSS variables in `assets/scss/_bootstrap-variables-dark.scss`:

```scss
// assets/scss/_bootstrap-variables-dark.scss
[data-bs-theme="dark"] {
  --bs-body-bg: #1a1a2e;
  --bs-body-color: #e0e0e0;
}
```

## Pin one theme on a layout

A layout that must always render in one theme, such as a customer-facing page with its own styling, leaves out
`settheme.html.twig` and sets the attribute itself:

```twig
<script nonce="{{ csp_nonce('script') }}">
    document.documentElement.setAttribute('data-bs-theme', 'light')
</script>
```

- Set it on `<html>`, not on `<body>`. The dark rules are scoped under `[data-bs-theme=dark]` on `<html>`, so a
  `data-bs-theme="light"` on `<body>` still leaves every one of them matching.
- Keep it a script, not a static attribute on `<html>`: with Turbo, navigating from an admin page in dark mode keeps
  the `<html>` element and its attribute, and only the script sets it back.
- The `nonce` is needed because `nelmio/security-bundle` sends a CSP that blocks inline scripts without one.

## Disable dark mode

1. Set `$enable-dark-mode: false` in `assets/scss/_bootstrap-variables.scss`
2. Remove or comment out the dark mode logo and its `{% if %}` block in `templates/navigation.html.twig`
3. Remove `{% include 'partials/themetoggler.html.twig' %}` from your base layout

## Troubleshooting

- **Toggle button not visible**: check that `themetoggler.html.twig` is included in your base template
- **Theme resets on page reload**: the theme is stored in `localStorage` (`theme`); verify storage isn’t blocked and that `templates/settheme.html.twig` is included early in your base layout
- **Dark variables not applying**: confirm `_bootstrap-variables-dark.scss` is imported after the Bootstrap variables
  file in your main SCSS entry point
