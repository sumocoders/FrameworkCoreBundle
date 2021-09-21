## Dark mode

### Disabled dark mode

- Remove the import of `Theme` from `imports.js`
- Remove all `theme.current` if statements and remove the dark mode code.
    - Remove dark mode logo and if statements on the light mode logo in `templates/navigation.html.twig`
    - Remove dark mode `encore_entry_link_tags` and if statement around it in `templates/head.html.twig`
- Hide the div with class `.dark-mode-switch` to hide the dark mode toggle.
