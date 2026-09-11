[Back to index](../index.md)

# Request lifecycle

Two independent listeners populate the navigational chrome (breadcrumbs, page `<title>`) shown on every page, so
templates never have to build that state themselves. Both are plain `kernel.controller` listeners that use manual
`ReflectionClass` introspection of the resolved controller — there is no per-attribute kernel event involved.

## Children

- [breadcrumb.md](breadcrumb.md) — `#[Breadcrumb]` attribute, `BreadcrumbListener`, `BreadcrumbTrail`
- [title.md](title.md) — `#[Title]` attribute, `TitleListener`, `PageTitle`

## Shared control flow

Both `BreadcrumbListener::onKernelController()` and `TitleListener::onKernelController()` are tagged in
`config/services.php` on the generic `kernel.controller` event (method `onKernelController`, priority `-1`), **not**
`kernel.controller_arguments`. This matters: at `kernel.controller` time, Symfony has resolved *which* controller
will run but has not yet resolved its argument values (no `#[MapEntity]` objects, no auto-cast scalars) — arguments
are only available as raw strings on `$request->attributes`. Both listeners work around this by doing their own
`EntityManager` repository lookups (`find()` / `findOneBy()`, replicating `#[MapEntity]`'s `mapping:` option) instead
of relying on Symfony's argument resolver.

An older revision of `CLAUDE.md` described an unmerged branch (`feature/symfony-8.1-controller-attribute-events`,
commit `8676a13`) that hooked `kernel.controller_arguments` with per-attribute events instead. That branch never
landed on `master`. Trust `config/services.php` and the listener classes over any prior written description.

Both listeners reflect on `$event->getController()` (unwrapping `[$controller, $method]` array callables to the
controller instance) and read attributes off the resolved class — see the child pages for the per-listener
resolution algorithm, precedence rules, and exceptions.
