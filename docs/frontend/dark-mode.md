# Dark mode

On page load we load the correct theme stylesheet in `templates/head.html.twig` based on the result of the `theme()`
twig function. We also use that variable to show the correct logo in `templates/navigation.html.twig`.

First time on the application we show the theme based on the users OS preferences.
Later when the user uses the theme switcher in the navigation, we set a cookie with the theme chosen in Javascript.

## Disabled dark mode

- Remove the import of `Theme` from `imports.js`
- Remove all `theme()` if statements and remove the dark mode code.
    - Remove dark mode logo and if statements on the light mode logo in `templates/navigation.html.twig`
    - Remove dark mode `encore_entry_link_tags` and if statement around it in `templates/head.html.twig`
- Hide the div with class `.dark-mode-switch` to hide the dark mode toggle.
