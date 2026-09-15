# Architecture conventions

## Source layout is layer-based, not feature-based

`src/` is organized by technical layer (`Attribute/`, `DBALType/`, `EventListener/`, `Form/`, `Menu/`,
`Pagination/`, `Service/`, `Twig/`, `ValueObject/`, `Enum/`, `Exception/`, `Logger/`, `Command/`, `Intl/`,
`Serializer/`, `DoctrineListener/`), not by domain feature (`src/{Layer}/{Feature}/`, the convention used in
consuming applications built on this bundle). That's expected here: this repo is a shared bundle providing
cross-cutting *subsystems* (breadcrumbs, audit trail, pagination, uploads, ...), not an app with domain features.

Consequence for `docs/*.md`: each file mirrors one of the bundle's subsystems (matching the grouping already
used in `docs/index.md`'s table, with a Usage section followed by an Internals section), not a feature — there's
no `contact/`/`company/`-style feature directory to key off of.

## Event listener registration

All event listener wiring lives in `config/services.php` via `->tag('kernel.event_listener', [...])` (or
`kernel.event_subscriber` for subscriber classes) — check that file for the actual event name/priority a listener
runs on rather than trusting a class's docblock or an out-of-date summary elsewhere; see
`docs/memory/mistakes.md` for a case where those had drifted apart.

## Config-driven service behavior

`Configuration.php` briefly had its first real option (`sentry_user_context.enabled`) but it was removed shortly
after introduction — the feature is now always active, and the tree is empty again (see
`docs/adr/0003-first-bundle-config-option-parameter-wiring.md`, updated to record both the introduction and the
removal). If a config option is added again, the established pattern was: `Extension::load()` calls
`processConfiguration()` and `$container->setParameter('sumo_coders_framework_core.<option>', ...)`;
`config/services.php` binds that parameter to the target service's constructor argument with
`->bind('<type> $name', param('...'))`, and the service itself no-ops at runtime when the flag is off. There is
no precedent for conditionally registering/removing service definitions (`hasDefinition()`/`removeDefinition()`)
based on config — follow the parameter+runtime-guard shape rather than introducing conditional wiring.

## Impersonation detection uses one shared idiom

Symfony's switch-user ("impersonation") feature is checked the same way everywhere in this bundle:
`AuditLogger::getImpersonatingUser()` and `SentryUserContextListener::getImpersonatorId()` both use
`isGranted('IS_IMPERSONATOR')` followed by an explicit `$token instanceof SwitchUserToken` guard (and an
`instanceof UserInterface` guard on the resulting user). This converged per
`docs/adr/0005-converge-auditlogger-impersonation-with-is-impersonator.md`, which supersedes
`docs/adr/0004-impersonation-detection-diverges-from-auditlogger.md` documenting the original divergence. No
shared helper was extracted — each method is a separate, structurally identical private method. Follow this
idiom for any new impersonation check.
