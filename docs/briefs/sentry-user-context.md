# Brief: Sentry user context listener

## Origin

Requested as a port of `vvkp`'s `App\EventListener\SentryUserContextListener`
(`src/EventListener/SentryUserContextListener.php` in `platform-vvkp-be`, fetched for reference via the GitLab API)
into `FrameworkCoreBundle`, generalized for use across all consuming projects and gated behind bundle config.

Design decisions below were made collaboratively before planning (see "Decisions" — do not revisit without new
information).

## Goal

On every main request, if there's an authenticated user, attach their identity to the current Sentry scope
(`Sentry\State\Scope`) so errors reported to Sentry are tied to a user. If the request is impersonated
(Symfony "switch user"), also attach the impersonator's identity as extra context. The whole feature must be
on by default and toggleable (off) via bundle config, and must not force Sentry onto projects that don't use it.

## Decisions (resolved, binding)

1. **User data source: generic `UserInterface::getUserIdentifier()`.** No marker interface, no reflection/duck
   typing, no coupling to any consuming project's User entity class. The listener only ever knows about
   `Symfony\Component\Security\Core\User\UserInterface`. Set `UserDataBag(id: $user->getUserIdentifier())` — no
   separate email field.
2. **Sentry is an optional dependency.** Do not add `sentry/sentry-symfony` to composer `require`. Add it to
   `suggest` instead. The listener autowires `?\Sentry\State\HubInterface $hub = null` (nullable, same pattern
   vvkp itself uses) so the bundle installs and works fine in projects that never touch Sentry.
3. **Config: single on/off flag**, default `true`. Something like:
   ```php
   sumo_coders_framework_core:
       sentry_user_context:
           enabled: true
   ```
   `Configuration.php` is currently intentionally empty ("No runtime bundle config is needed" per this repo's
   CLAUDE.md) — this is the first real config surface added to the bundle. Update that CLAUDE.md line once this
   lands.
4. **Impersonation tracking is in scope.** Port vvkp's approach: detect via
   `$security->isGranted('IS_IMPERSONATOR')` + the token being a `SwitchUserToken`, pull the original token's
   user, and if it's a `UserInterface`, attach its identifier as extra Sentry context (e.g. context key
   `impersonation` ⇒ `['impersonator_id' => ...]`), mirroring the shape of vvkp's `impersonator_id` /
   `impersonator_email` pair minus the email field (see decision 1 — no email anywhere in this feature).

## Requirements

- New listener class in `src/EventListener/` (e.g. `SentryUserContextListener`), subscribing to
  `KernelEvents::REQUEST`, main requests only — follow the existing style of `BreadcrumbListener` /
  `TitleListener` in that directory.
- Constructor takes `Security $security` (already a bundle dependency via `symfony/security-bundle`) and nullable
  `?HubInterface $hub = null`.
- No-ops when: `$hub === null`, not a main request, feature disabled via config, or no authenticated user.
- Config: extend `Configuration.php` (currently an empty tree) with the `sentry_user_context.enabled` boolean,
  and wire it through `SumoCodersFrameworkCoreExtension::load()` so the listener is only active/registered when
  enabled — decide during planning whether that means conditionally registering the service, setting a
  constructor argument via a container parameter, or removing the service definition when disabled, whichever
  fits this bundle's existing `config/services.php` (PHP-format, no YAML) registration style best.
- Composer: add `sentry/sentry-symfony` under `suggest` with a short reason string.
- Docs: add `docs/sentry-user-context.md` following this repo's existing doc format (purpose, prerequisites,
  usage, options table, examples, troubleshooting) and link it from `docs/index.md`'s subsystem table. Update the
  main `CLAUDE.md` subsystem table/request-lifecycle section too if this listener fires on every request.
- Tests: **note that this repo currently has no `tests/` directory or PHPUnit setup at all**, despite the
  `phpunit` command being documented in CLAUDE.md. Flag this during planning rather than assuming test
  scaffolding exists — decide (and get sign-off at the gate) whether this feature is the one that introduces
  `tests/` + `phpunit.xml`, or whether it ships untested consistent with everything else in the bundle today.

## Out of scope

- Anything beyond user id + impersonator id (no email, no extra profile fields) per decision 1.
- Changing how/when Sentry itself is bootstrapped in consuming projects (`config/bundles.php`, `SENTRY_DSN`, etc.)
  — that's each project's own concern, unaffected by this bundle.
