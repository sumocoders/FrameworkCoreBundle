[Back to index](index.md)

# Breadcrumb resolution

Builds the navigational trail shown above page content, from `#[Breadcrumb]` attributes on the matched controller;
implemented as a `kernel.controller` listener that reflects on the resolved controller class and pushes
`ValueObject\Breadcrumb` instances onto the request-scoped `BreadcrumbTrail`.

`src/EventListener/BreadcrumbListener.php`

## Workflow

1. `onKernelController()` unwraps the controller, resets `BreadcrumbTrail` (only `if ($event->isMainRequest())`, so
   sub-requests append to the parent's trail instead of clearing it), then calls `processBreadcrumbs($controller)`.
2. `processBreadcrumbs()` reflects the controller class and iterates **every public method** on it (
   `getMethods(ReflectionMethod::IS_PUBLIC)`) — not just the method that matched the current route — calling
   `processAttributeFromMethod()` for each. Throws `InvalidArgumentException` if the class is abstract.
3. `processAttributeFromMethod($method, $class, ?Route $route = null)`:
   - Reads `#[Breadcrumb]` off the method.
   - Class-level attributes are merged in **only** when the method is `__invoke`, or the method already carries at
     least one `#[Breadcrumb]` of its own — matching the "invokable controllers put attributes on the class" and
     "multi-action controllers keep attributes per-method" conventions from `docs/breadcrumb.md`. When both are
     merged, method attributes come first and class attributes are appended after (`array_merge($methodAttrs,
     $classAttrs)`), so they're added to the trail in that order.
   - If the attribute has a `parent:` route, `addBreadcrumbsForParent()` recurses **before** the current attribute is
     added, so ancestors land earlier in the trail.
   - `generateBreadcrumb()` builds the `ValueObject\Breadcrumb`; a thrown `EntityNotFoundException` is swallowed here
     (silently skips that one breadcrumb) — every other exception propagates.
4. `generateBreadcrumb()`:
   - `{expr}` titles: splits on the first `.` into `$attributeName` / `$propertyPath`. The attribute must exist on
     `$request->attributes` (raw route parameter — a scalar id/slug at this point in the lifecycle, not yet a
     resolved entity). The listener re-resolves the entity itself via
     `EntityManager::getRepository($type)->find()` or, if the matching controller parameter carries `#[MapEntity]`,
     `->findOneBy([$mapping[$attributeName] => $id])` — it does not read the already-resolved argument because none
     exists yet at `kernel.controller` time.
   - `route:` present → resolves route parameters (`resolveRouteParameters()`) and generates an absolute URL; the
     resulting breadcrumb carries a `route` and templates render it as a link.
   - `parameters:` present → each value is resolved the same way as `{expr}` and passed to
     `TranslatorInterface::trans($title, $parameters)`.
   - Otherwise the title is used as-is (translation happens later, in the Twig template — see `docs/breadcrumb.md`).

## The `parent:` chaining mechanism

`addBreadcrumbsForParent(Route $parent)`:
- Looks up the parent route's controller/method via `getRouteInformation()`, which scans the full
  `RouterInterface::getRouteCollection()` matching on the route key or its `_canonical_route` default (locale-prefixed
  route names resolve to the same canonical breadcrumb).
- Reflects that controller/method and re-enters `processAttributeFromMethod($method, $class, new Route($routeName))`
  — passing a `Route` for **itself**. `setRoute()` on the found attribute forces that parent crumb to always become a
  link to its own route, even if the source `#[Breadcrumb]` on the parent controller never declared `route:`. This is
  the one case where `hasRoute()` can be true without the developer writing `route:` explicitly.
- Recurses further if that parent attribute itself has a `parent:`, building the full ancestor chain before the
  current page's own crumb is appended.
- `resolveRouteParameters()` auto-fills route parameters from the *current* request's route attributes
  (`$request->attributes->all()`) when the parent route's required parameters share a name with ones already in the
  URL (e.g. `/{author}/{book}` filling `author` for a parent named `author_detail`). Objects are reduced via
  `->getId()`. Missing required parameters raise `RuntimeException`.

## Class vs. method precedence — gotcha

Because `processBreadcrumbs()` walks **all** public methods of the class (not only the matched one), a multi-action
controller where more than one action carries its own `#[Breadcrumb]` will have breadcrumbs generated for *every*
action on every request to *any* of them. A static-string breadcrumb on an unrelated action is silently added to the
trail too. A `{param}` breadcrumb on an unrelated action whose parameter isn't present on the current request throws
an uncaught `RuntimeException` (not `EntityNotFoundException`, so it is **not** swallowed) — it can break an
unrelated route. This is why the project convention is invokable, single-action controllers with attributes on the
class.

## Exceptions

| Exception | Thrown when | Caught? |
|---|---|---|
| `InvalidArgumentException` | Resolved controller class is abstract | No — propagates |
| `Exception\Breadcrumb\EntityNotFoundException` | `{expr}`/`parameters:` repository lookup returns a non-object | Yes, in `processAttributeFromMethod()` — that one breadcrumb is skipped |
| `RuntimeException` | Route parameter name missing from `$request->attributes`; object interpolation used without a `.property` path; parent route name not found in the router; required route/parent parameters can't be resolved | No — propagates and fails the request |
| `Exception\MissingOptionException` | Defined but currently unused anywhere in the codebase — reserved | n/a |

## Related services

- `Service\BreadcrumbTrail` — `Iterator`/`Countable` over `ValueObject\Breadcrumb[]`; `reset()`/`add()`/`all()`.
  Registered as `framework.breadcrumb_trail`, aliased for direct `BreadcrumbTrail::class` injection.
- `Service\PageTitle` reads the trail (reversed) as its title fallback — see [title.md](title.md).
- `Service\Fallbacks` (extends `ParameterBag`) holds the `fallbacks` container parameter (e.g. `site_title`); not used
  directly by the breadcrumb listener, only by `PageTitle`.
