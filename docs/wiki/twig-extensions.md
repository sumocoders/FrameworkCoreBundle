[Back to index](index.md)

# Twig extensions

The bundle exposes several small, single-purpose Twig extension classes rather than one monolithic extension. Each is
a plain class using PHP 8 attributes (`#[AsTwigFunction]` / `#[AsTwigFilter]`) instead of implementing
`ExtensionInterface` and registering nodes manually.

## Registration

All four are registered in `config/services.php` and tagged `twig.attribute_extension`, which is what makes Symfony's
Twig bridge scan them for `#[AsTwigFunction]`/`#[AsTwigFilter]` attributes — a class with those attributes but without
this tag is inert. There is no separate `TwigExtension` service class per family; each concern gets its own extension
class.

## The classes

| Class | Path | Registers | Purpose |
|---|---|---|---|
| `FrameworkExtension` | `src/Twig/FrameworkExtension.php` | filter `ucfirst`, functions `theme`, `sidebarIsOpen` | Cookie-driven UI state (dark/light theme, sidebar collapsed state) read from the current request. |
| `PaginatorExtension` | `src/Twig/PaginatorExtension.php` | function `pagination` | Renders the pager block for a `Paginator` instance. |
| `ContentExtension` | `src/Twig/ContentExtension.php` | function `content` | Reads a raw file from `public/` by path and returns it as safe HTML. |
| `AssetContentExtension` | `src/Twig/AssetContentExtension.php` | function `asset_content` | Reads the *AssetMapper-resolved* content of an asset by logical path and returns it as safe HTML. |

### `FrameworkExtension`

Reads `RequestStack::getCurrentRequest()` cookies (`theme`, `sidebar_is_open`) and returns CSS-class-ready strings
(`theme-light`, `theme-dark`, ...) or booleans. Defaults to light theme / open sidebar when there is no current
request (e.g. CLI context) or the cookie is absent. `ucfirst` is a plain static wrapper around PHP's `ucfirst()`.

### `PaginatorExtension`

`pagination(paginator)` uses `needsEnvironment: true` to load and render the `pager` block from
`@SumoCodersFrameworkCore/Twig/pagination.html.twig` directly (`$env->load(...)->renderBlock(...)`), rather than
`{% include %}`. It derives the current route and route+query params from the request to build pager links, and
throws a `RuntimeException` if called from a sub-request (`getParentRequest()` is not null) since the route can't be
reliably inferred there.

### `ContentExtension` vs `AssetContentExtension`

`ContentExtension::getContent(path)` is the generic case: it resolves `path` against the `%kernel.project_dir%/public`
folder (autowired via `#[Autowire]`) using `Filesystem::readFile()` and returns the raw file content, marked
`isSafe: ['html']`. It has no knowledge of AssetMapper — it's just "read a file under `public/` and inline it".

`AssetContentExtension` was generalized *from* that same pattern but targets `AssetMapperInterface` instead of the
filesystem directly: `asset_content(path)` resolves `path` as an AssetMapper logical path
(`$assetMapper->getAsset($path)`) and returns the mapped asset's `->content`, throwing `AssetNotFoundException` if the
mapper can't resolve it. This makes it AssetMapper-version-aware (works with mapped/versioned assets, not just static
public files) and is what the mail base template uses to inline compiled CSS — see
[mail.md](mail.md).

## Adding a new one

Follow the existing pattern: a small `readonly` (or `final readonly`) class in `src/Twig/`, methods marked with
`#[AsTwigFunction('name')]` or `#[AsTwigFilter('name')]` (add `isSafe: ['html']` if the return value is raw
markup/CSS), then register it in `config/services.php` with `->tag('twig.attribute_extension')`. No manual
`getFunctions()`/`getFilters()` array is needed.
