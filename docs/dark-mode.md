# Dark mode

The bundle uses Bootstrap 5.3 color themes for dark mode. The active theme is stored in `localStorage` (`theme`) and applied
via the `data-bs-theme` attribute on `<html>` (see `templates/settheme.html.twig`).
No server-side cookie is required for the Bootstrap theme switcher to work.

Color variables are defined in `assets/scss/_bootstrap-variables-dark.scss`. See
the [Bootstrap color modes documentation](https://getbootstrap.com/docs/5.3/customize/color-modes/) for all available
CSS variables.

## How it works

1. `FrameworkExtension` (Twig) provides `theme()`, returns `'theme-light'` or `'theme-{cookieValue}'`
2. A Stimulus controller (`dark-mode`) toggles the cookie and updates `data-bs-theme` on `<html>` without a page reload
3. `templates/partials/themetoggler.html.twig` renders the toggle button

## Customize variables

Override Bootstrap dark-mode CSS variables in `assets/scss/_bootstrap-variables-dark.scss`:

```scss
// assets/scss/_bootstrap-variables-dark.scss
[data-bs-theme="dark"] {
  --bs-body-bg: #1a1a2e;
  --bs-body-color: #e0e0e0;
}
```

## Disable dark mode

1. Set `$enable-dark-mode: false` in `assets/scss/_bootstrap-variables.scss`
2. Remove or comment out the dark mode logo and its `{% if %}` block in `templates/navigation.html.twig`
3. Remove `{% include 'partials/themetoggler.html.twig' %}` from your base layout

## Troubleshooting

- **Toggle button not visible**: check that `themetoggler.html.twig` is included in your base template
- **Theme resets on page reload**: the Stimulus controller writes a `theme` cookie; verify cookies are not blocked and
  the domain matches
- **Dark variables not applying**: confirm `_bootstrap-variables-dark.scss` is imported after the Bootstrap variables
  file in your main SCSS entry point
