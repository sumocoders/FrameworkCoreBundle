# EditorJS

The bundle themes [EditorJS](https://editorjs.io) so it follows the application's Bootstrap colour mode in both
light and dark. It does not ship EditorJS itself: the JavaScript, the Stimulus controller and the form widget stay
in the consuming application.

## Prerequisites

- **EditorJS 2.28 or newer.** The theming hangs off the custom properties that `.ce-popover` gained in 2.28, plus
  the `.cdx-search-field` markup introduced with it. Older versions render with EditorJS's own light palette and
  no error.
- EditorJS registered in the application's `importmap.php`. `importmap:require @editorjs/editorjs` fails, because
  jsDelivr cannot bundle the package as ESM. Vendor `dist/editorjs.mjs` (and each tool's `.mjs`) into
  `assets/editorjs/` and map them by path instead.
- A CSP nonce shim if the application enforces `style-src`. EditorJS injects its stylesheet through a generated
  `<style>` tag, which CSP blocks. See [nonce-generator.md](nonce-generator.md).

## Usage

Wrap the editor holder in `.editor-js`, and keep the value in a hidden textarea:

```twig
<div data-controller="editorjs">
    <div data-editorjs-target="holder" class="form-control editor-js"></div>
    <textarea {{ block('widget_attributes') }} data-editorjs-target="input" hidden>{{ value }}</textarea>
</div>
```

`.form-control` gives the editor the standard field border; `.editor-js` gives it a minimum height so an empty
editor is still a visible target.

## What is styled

| Selector                                        | What it covers                                                  |
|-------------------------------------------------|-----------------------------------------------------------------|
| `.editor-js`                                    | Minimum height of the editor area                                |
| `.ce-popover`                                   | The block popover: surface, text, borders, icons, hover, focus    |
| `.ce-inline-toolbar`                            | The toolbar shown when text is selected                           |
| `.ce-toolbar__plus`, `.ce-toolbar__settings-btn`| The plus button and the block settings handle                     |
| `.cdx-search-field`                             | The popover's search input and its placeholder                    |
| `.cdx-notify--error`                            | Error notifications                                               |
| `.cdx-input:empty::before`                      | Placeholder text inside tool inputs                               |

## Troubleshooting

**The popover is still white in dark mode.** The EditorJS version predates 2.28 and does not read
`--color-background`. Check `.ce-popover` in the browser inspector: if it has no `--color-*` custom properties of
its own, upgrade EditorJS.

**The editor has no styling at all, in either mode.** EditorJS's own stylesheet was blocked by CSP. Look for a
`style-src` violation in the console and add the nonce shim.

**Hover states are invisible.** The project overrides `$gray-700` or `$gray-850` to the same value as
`$body-bg-dark`. Keep them distinct.

## Internals

### Custom properties over selector overrides

EditorJS declares its whole popover palette as custom properties on `.ce-popover`:

```
--color-background, --color-text-primary, --color-text-secondary, --color-border, --color-shadow,
--color-border-icon, --color-background-item-hover, --color-background-item-focus
```

`.ce-popover__container`, `.ce-popover-item`, `.ce-popover-item__title` and `.ce-popover-item__icon` all read from
those, so eight assignments replace roughly fifteen selector overrides and keep working when EditorJS reshuffles
its internal markup. Only the handful of places where EditorJS hardcodes a colour (`.ce-toolbar__plus` at
`#1d202b`, `.cdx-search-field` at `#F8F8F8`) still need a selector.

### Why not `--bs-secondary-bg`

The obvious Bootstrap token for a hover band is `--bs-secondary-bg`, but this bundle's fork sets both
`$body-bg-dark` and `$body-secondary-bg-dark` to `$gray-800`. In dark mode the two tokens are the same colour, so
a hover painted with `--bs-secondary-bg` on a `--bs-body-bg` surface is invisible. The file uses the same Sass
greys as the rest of the components instead: `$gray-850` for elevated surfaces (as in `_cards.scss` and
`_list-group.scss`), `$gray-700` for hover (as in `_forms.scss`), `$gray-600` for borders.

Sass variables need `#{}` interpolation to be usable as custom property values.

### Known limitation

The bundle pins no EditorJS version, so nothing enforces the 2.28 floor. A project on an older release gets the
`.editor-js` height rule and nothing else.
