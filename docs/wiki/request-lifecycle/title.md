[Back to index](index.md)

# Title resolution

Sets the page `<title>`/`<h1>` text so templates don't need per-page title logic; implemented as a `kernel.controller`
listener that reflects `#[Title]` off the resolved controller and writes to the request-scoped `PageTitle` service,
falling back to the breadcrumb trail when no `#[Title]` is present.

`src/EventListener/TitleListener.php`

## Workflow

1. Unwraps the controller the same way `BreadcrumbListener` does, then reflects **all** methods of the class —
   `getMethods()` with no visibility filter, unlike `BreadcrumbListener`'s `IS_PUBLIC`-only scan.
2. For each method, `getTitleAttributes()` returns the method's own `#[Title]` attributes, or — only when the method
   is named `__invoke` and has none of its own — the declaring class's `#[Title]` attributes instead. Methods with
   zero attributes are skipped (`continue`). Because `#[Title]` is not repeatable, this is at most one attribute per
   method.
3. `processParameters()` resolves the *method's* reflection parameters against `$request->attributes->all()`,
   re-running `#[MapEntity]` lookups itself (same rationale as `BreadcrumbListener`: at `kernel.controller` time the
   argument resolver hasn't run yet, so objects aren't available on the request attributes — only raw scalars).
4. For each `#[Title]` attribute found:
   - `extend: false` → `pageTitleService->setTitle($rawTitle)` **verbatim** (no translation, no `{param}`
     interpolation, no parent chain, no site title suffix) and then `return;` — this exits the whole listener
     method immediately, not just the current iteration, so no other method on the class is processed afterward once
     an `extend: false` title is hit.
   - Otherwise: `processTitle()` resolves placeholders and translates, `getTitleFromParent()` prepends ancestor
     titles if `parent:` is set, and the final string is suffixed with `' - ' . $this->fallbacks->get('site_title')`.

## Placeholder resolution (`processTitle`)

Regex-matches every `{...}` in the title. Each match is split on `.`: with a `.`, the first segment must be a key in
`$parameters` and the value is read via `PropertyAccessorInterface::getValue()` on the object; without a `.`, the
whole match is used as a scalar parameter key directly (unlike breadcrumbs, `{param}` on a plain scalar controller
argument *is* supported here). Then `TranslatorInterface::trans($title)` — the interpolated string is translated
*after* substitution, so any translation catalogue entry for a title with placeholders must be written with the
already-substituted final value in mind, not the original `{token}` template. A placeholder key absent from
`$parameters` throws a plain `\Exception`, not `RuntimeException` (differs from `BreadcrumbListener`'s equivalent
check).

## The `parent:` chaining mechanism

`getTitleFromParent(Route $parent, $parameters)` looks up the parent route's controller/method via its own
`getRouteInformation()` (route collection scan, same canonical-route matching as `BreadcrumbListener`), reflects that
method, and re-runs `getTitleAttributes()` + `processTitle()` on it — using the **current** request's already-resolved
`$parameters`, not the parent route's own arguments. It recurses again if that parent's `#[Title]` itself has a
`parent:`. Each level is prefixed with `' - '`, so the final string reads `Current - Parent - Grandparent`. There is
no HTTP sub-request involved despite what `docs/title.md`'s troubleshooting section implies ("the chain resolves by
dispatching a subrequest") — it's a direct reflection + method call within the same listener invocation.

## Resolution order (in `PageTitle`)

`PageTitle::getTitle()`:
1. Explicit title set by `TitleListener::setTitle()` this request, if any.
2. Otherwise, `BreadcrumbTrail::all()` reversed, each crumb's title passed through `trans()`, joined with `' - '`,
   with `fallbacks.site_title` appended as the last element.
3. If the trail is empty, `fallbacks.site_title` alone.

This means `TitleListener` and `BreadcrumbListener` are independent — `PageTitle` only reads `BreadcrumbTrail` as a
fallback, it does not need `TitleListener` to have run at all. `PageTitle::__toString()` makes it directly usable as a
string in Twig.

## Gotchas

- Same multi-action hazard as breadcrumbs: reflecting **every** method (public and non-public) means a `{param}`
  title on an unrelated action can throw if that request doesn't carry the same-named attribute.
- `extend: false`'s early `return` means if a class has multiple attributed methods and an earlier-reflected one
  (reflection order, not declaration/route order) uses `extend: false`, later methods' `#[Title]` attributes are
  never even inspected for that request.
- `Fallbacks::get('site_title')` is looked up positionally in `processTitle`'s non-`extend:false` path only — verbatim
  titles never see it.

## Exceptions

| Exception | Thrown when |
|---|---|
| `\Exception` (generic) | A `{param}` in the title has no matching key in the method's resolved parameters |
| `RuntimeException` | Indirectly, if `#[MapEntity]` repository resolution fails the same way as in `BreadcrumbListener` |

## Related services

- `Service\PageTitle` — see above; registered as `framework.page_title`, aliased for `PageTitle::class`.
- `Service\Fallbacks` — holds the `fallbacks` container parameter (set in `config/services.php` to `[]` by default;
  consuming apps override it, e.g. `site_title`).
- See [breadcrumb.md](breadcrumb.md) for `BreadcrumbTrail`, which this service reads as a fallback.
