# Dark mode

We use bootstrap color themes for dark mode styling. See the [documentation](https://getbootstrap.com/docs/5.3/customize/color-modes/) for more information.
Color variables are defined in `assets/scss/_bootstrap-variables-dark.scss`.

## Disable dark mode

Set `$enable-dark-mode` to false in `assets/scss/_bootstrap-variables.scss` to disable dark mode completely.

- Remove dark mode logo and if statements on the light mode logo in `templates/navigation.html.twig`
- Hide or remove themetoggler.html.twig from your base layout
