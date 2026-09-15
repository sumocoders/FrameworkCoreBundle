[Back to index](index.md)

# Twig extensions

The bundle ships four small, single-purpose Twig extension classes rather than one monolithic extension: cookie-driven
UI state, the pagination widget, and two ways to inline a file's raw content into a template.

## Usage

### `FrameworkExtension` — theme and sidebar state

Registers the `ucfirst` filter and the `theme()` / `sidebarIsOpen()` functions. `theme()` and `sidebarIsOpen()` read
the `theme` and `sidebar_is_open` cookies from the current request (via `RequestStack`) to drive dark/light theme and
sidebar-collapsed UI state in the base layout:

```twig
<body class="{{ theme() }}">
    <div class="sidebar {{ sidebarIsOpen() ? 'sidebar-open' : 'sidebar-collapsed' }}">
        ...
    </div>
</body>
```

`theme()` returns `theme-light` or `theme-dark` (prefixing whatever the `theme` cookie holds), and `sidebarIsOpen()`
returns a boolean, `true` unless the `sidebar_is_open` cookie is explicitly set to `'false'`. Both fall back to their
open/light defaults when there is no current request at all (CLI commands, console context) or the cookie is absent,
so templates rendered outside a real HTTP request never break.

`ucfirst` is a plain filter wrapper around PHP's `ucfirst()`:

```twig
{{ 'title'|ucfirst }} {# Title #}
```

### `PaginatorExtension` — the `pagination()` function

Registers `pagination(paginator)`, used to render the pager widget for a `Paginator` instance. See
[pagination.md](pagination.md) for the full pagination workflow; in a template it's used as:

```twig
{% if items.hasToPaginate %}
    {{ pagination(items) }}
{% endif %}
```

It throws a `RuntimeException` if called from a sub-request (e.g. inside `{% render %}` or a forwarded controller),
since the current route can't be reliably inferred there. Call it only from templates rendered for the main request.

### `ContentExtension` — inline a file from `public/`

Registers `content(path)`, which reads a raw file from `public/` by path and outputs it as safe HTML:

```twig
{{ content('/images/logo.svg') }}
```

`path` is resolved against `%kernel.project_dir%/public`. Use this for static files that live directly in `public/`
and aren't processed by AssetMapper (for example, inlining an SVG icon).

### `AssetContentExtension` — inline an AssetMapper asset

Registers `asset_content(path)`, which reads an AssetMapper-resolved asset's content by logical path and outputs it
as safe HTML:

```twig
{{ asset_content('styles/mail.css') }}
```

This exists separately from `content()` because AssetMapper assets aren't plain files under `public/`: `path` is a
logical AssetMapper path, resolved through `AssetMapperInterface::getAsset()` rather than read straight off disk, so
it stays correct for mapped/versioned assets. This is what the bundle's mail base template uses to inline compiled
CSS — see [mails.md](mails.md). It throws `Symfony\Component\Asset\Exception\AssetNotFoundException` if the asset
mapper can't resolve `path`.

## The classes

| Class                   | Path                                     | Registers                                      | Purpose                                                                    |
|-------------------------|-------------------------------------------|-------------------------------------------------|-----------------------------------------------------------------------------|
| `FrameworkExtension`     | `src/Twig/FrameworkExtension.php`         | filter `ucfirst`, functions `theme`, `sidebarIsOpen` | Cookie-driven UI state (theme, sidebar collapsed) read from the current request. |
| `PaginatorExtension`     | `src/Twig/PaginatorExtension.php`         | function `pagination`                            | Renders the pager block for a `Paginator` instance.                        |
| `ContentExtension`       | `src/Twig/ContentExtension.php`           | function `content`                               | Reads a raw file from `public/` by path, outputs it as safe HTML.          |
| `AssetContentExtension`  | `src/Twig/AssetContentExtension.php`      | function `asset_content`                         | Reads an AssetMapper-resolved asset's content by logical path, outputs it as safe HTML. |

## Adding a new one

Follow the same pattern for a new extension:

1. Create a `readonly` (or `final readonly`) class in `src/Twig/`.
2. Add methods marked with `#[AsTwigFunction('name')]` or `#[AsTwigFilter('name')]`. Add `isSafe: ['html']` when the
   return value is raw markup that shouldn't be escaped.
3. Register the service in `config/services.php` and tag it `twig.attribute_extension`:

   ```php
   ->set('framework.my_extension', MyExtension::class)
   ->tag('twig.attribute_extension')
   ```

That tag is what makes Symfony's Twig bridge scan the class for `#[AsTwigFunction]`/`#[AsTwigFilter]` attributes — no
manual `getFunctions()`/`getFilters()` array is needed, and a class with those attributes but without the tag is
inert.

## Troubleshooting

- **Function/filter not found in templates**: check the service is registered in `config/services.php` and tagged
  `twig.attribute_extension` — without the tag the attributes are never scanned.
- **`pagination()` throws `RuntimeException`**: it was called while rendering a sub-request; only call it from a
  template rendered for the main request.
- **`asset_content()` throws `AssetNotFoundException`**: the logical path doesn't resolve through AssetMapper; check
  the path matches an asset under one of the configured asset paths, not a raw path under `public/`.
- **`theme()`/`sidebarIsOpen()` return the default when a cookie is set**: confirm there is a current request
  (`RequestStack::getCurrentRequest()`) — both fall back to their light/open defaults outside of an HTTP request.
