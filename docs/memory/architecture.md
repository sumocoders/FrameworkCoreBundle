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

## Config-driven service behavior

`Configuration.php` was an empty tree until the sentry-user-context feature added its first real option
(`sentry_user_context.enabled`). The pattern: `Extension::load()` calls `processConfiguration()` and
`$container->setParameter('sumo_coders_framework_core.<option>', ...)`; `config/services.php` binds that
parameter to the target service's constructor argument with `->bind('<type> $name', param('...'))`, and the
service itself no-ops at runtime when the flag is off. There is no precedent for conditionally
registering/removing service definitions (`hasDefinition()`/`removeDefinition()`) based on config — follow the
parameter+runtime-guard shape for future config options rather than introducing conditional wiring.

## Two impersonation-detection idioms coexist

Symfony's switch-user ("impersonation") feature is checked two different ways in this bundle:
`AuditLogger::getImpersonatingUser()` uses `isGranted('ROLE_PREVIOUS_ADMIN')` with no `instanceof
SwitchUserToken` guard; `SentryUserContextListener::getImpersonatorId()` uses `isGranted('IS_IMPERSONATOR')` +
an explicit `$token instanceof SwitchUserToken` check. Both are correct, deliberately diverging (see
`docs/adr/0004-impersonation-detection-diverges-from-auditlogger.md`) — there is no single canonical pattern to
copy. Check both existing call sites before adding a third.
