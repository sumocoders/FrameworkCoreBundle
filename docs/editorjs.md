# EditorJS

The bundle themes [EditorJS](https://editorjs.io) so it follows the application's Bootstrap colour mode in both
light and dark. It ships styling only: the JavaScript, the Stimulus controller and the form integration stay in the
consuming application, and the bundle makes no assumption about how they are wired.

It themes the editor UI. Content rendered from stored EditorJS JSON is the application's own markup and is not
covered here.

## Prerequisites

- **EditorJS 2.28 or newer.** The theming hangs off the custom properties that `.ce-popover` gained in 2.28, plus
  the `.cdx-search-field` markup introduced with it. Older versions render with EditorJS's own light palette and
  no error.
- **EditorJS loaded in a way importmap supports.** See below.
- **A CSP that allows EditorJS's injected stylesheet.** EditorJS writes its CSS into a generated `<style>` tag. An
  application whose `style-src` keeps `'unsafe-inline'` needs nothing; one that uses a nonce or hash instead needs
  the nonce shim from [nonce-generator.md](nonce-generator.md).

### Loading EditorJS

`importmap:require @editorjs/editorjs` fails, because jsDelivr cannot bundle the core as ESM
([jsdelivr#18574](https://github.com/jsdelivr/jsdelivr/issues/18574),
[symfony#53999](https://github.com/symfony/symfony/issues/53999)). The tool packages (`@editorjs/header`,
`@editorjs/table`, ...) map through importmap normally; only the core needs a workaround. Both approaches below
are in use across projects, and the theming works with either.

| Approach                | How                                                                                                              | Trade-off                                                                    |
|-------------------------|------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------|
| Import from a CDN       | `import Editor from 'https://esm.sh/@editorjs/editorjs@latest'` in the controller; add `https://esm.sh` to `script-src` and `connect-src` | Nothing to vendor, but a runtime dependency on a third party and two CSP entries |
| Vendor the ESM build    | Copy `dist/editorjs.mjs` (and each tool's `.mjs`) into `assets/editorjs/` and map them by path in `importmap.php`  | Self-hosted, no CSP change, but upgrades are manual                            |

## Usage

The theming targets EditorJS's own class names (`.ce-*`, `.cdx-*`), so it applies as soon as the bundle's
stylesheet is loaded, whatever markup the editor is mounted in. Controller names, target names and the element
that holds the editor are the application's choice; nothing in the bundle depends on them.

One class is the application's to add: `.editor-js` gives the holder a minimum height, so an empty editor is still
a visible target. It is optional.

Two integration shapes are in use.

**A form type with a form theme block.** The type extends `TextareaType` and sets its own block prefix; the theme
renders the hidden textarea plus a holder next to it:

```twig
{% block editor_js_row %}
    <div class="form-group" data-controller="editor-js">
        {{ form_label(form) }}
        {{ form_widget(form, {attr: {class: 'visually-hidden'}}) }}
        <div data-editor-js-target="holder" class="editor-js"></div>
    </div>
    {{ form_errors(form) }}
{% endblock %}

{% block editor_js_widget %}
    {% set attr = attr|merge({'data-editor-js-target': 'textarea'}) %}
    {{ form_widget(form, {attr: attr}) }}
{% endblock %}
```

**Markup written directly in a template:**

```twig
<div data-controller="editorjs">
    <div data-editorjs-target="holder" class="form-control editor-js"></div>
    <textarea {{ block('widget_attributes') }} data-editorjs-target="input" hidden>{{ value }}</textarea>
</div>
```

`.form-control` is what gives the editor the standard field border. Without it the editor sits on the page
background, which is what a full-width content editor usually wants.

## What is styled

| Selector                                        | What it covers                                                  |
|-------------------------------------------------|-----------------------------------------------------------------|
| `.editor-js`                                    | Minimum height of the editor area (opt-in)                       |
| `.ce-popover`                                   | The block popover: surface, text, borders, icons, hover, focus    |
| `.ce-inline-toolbar`                            | The toolbar shown when text is selected                           |
| `.ce-toolbar__plus`, `.ce-toolbar__settings-btn`| The plus button and the block settings handle                     |
| `.cdx-search-field`                             | The popover's search input and its placeholder                    |
| `.cdx-notify--error`                            | Error notifications                                               |
| `.cdx-input:empty::before`                      | Placeholder text inside tool inputs                               |

Custom tools written in a project bring their own class names and their own styling. The bundle covers the editor
chrome, not the tools.

## Troubleshooting

**The popover is still white in dark mode.** The EditorJS version predates 2.28 and does not read
`--color-background`. Check `.ce-popover` in the browser inspector: if it has no `--color-*` custom properties of
its own, upgrade EditorJS.

**The editor has no styling at all, in either mode.** EditorJS's own stylesheet was blocked by CSP. Look for a
`style-src` violation in the console and add the nonce shim.

**The editor never appears and the console shows a module or CORS error.** The core is not loading. Check the
loading approach: a CDN import needs `script-src` and `connect-src` entries, a vendored build needs the `.mjs`
path present in `importmap.php`.

**The editor area collapses to a single line when empty.** The holder has no `.editor-js` class.

**Hover states are invisible.** The project overrides `$gray-700` or `$gray-850` to the same value as
`$body-bg-dark`. Keep them distinct.

## Internals

### Only EditorJS's own classes, plus one opt-in

The stylesheet deliberately hooks nothing but EditorJS's generated class names and `.editor-js`. That is why the
same file works for a form-type integration, for hand-written template markup, and for projects that mount the
editor from their own controller under any name. Adding a project's wrapper class here would tie the bundle to one
application's markup.

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
