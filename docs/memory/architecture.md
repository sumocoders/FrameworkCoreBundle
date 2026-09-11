# Architecture conventions

## Source layout is layer-based, not feature-based

`src/` is organized by technical layer (`Attribute/`, `DBALType/`, `EventListener/`, `Form/`, `Menu/`,
`Pagination/`, `Service/`, `Twig/`, `ValueObject/`, `Enum/`, `Exception/`, `Logger/`, `Command/`, `Intl/`,
`Serializer/`, `DoctrineListener/`), not by domain feature (`src/{Layer}/{Feature}/`, the convention used in
consuming applications built on this bundle). That's expected here: this repo is a shared bundle providing
cross-cutting *subsystems* (breadcrumbs, audit trail, pagination, uploads, ...), not an app with domain features.

Consequence for `docs/wiki/`: top-level namespaces mirror the bundle's subsystems (matching the grouping already
used in `docs/index.md`'s table), not features — there's no `contact/`/`company/`-style feature directory to
key off of.

## Event listener registration

All event listener wiring lives in `config/services.php` via `->tag('kernel.event_listener', [...])` (or
`kernel.event_subscriber` for subscriber classes) — check that file for the actual event name/priority a listener
runs on rather than trusting a class's docblock or an out-of-date summary elsewhere; see
`docs/memory/mistakes.md` for a case where those had drifted apart.
