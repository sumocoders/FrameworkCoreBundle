[Back to index](index.md)

# Sentry user context

Attaches the current request's authenticated user (and, when impersonated, the impersonating admin) to the
Sentry error-tracking scope, so exceptions reported to Sentry can be traced back to who was using the app. On by
default, but still inert unless Sentry itself is present — see `docs/sentry-user-context.md` for the
consumer-facing setup/options. Decision rationale for the choices below lives in
`docs/adr/0001-sentry-user-identity-generic-identifier-only.md`,
`docs/adr/0002-sentry-symfony-suggest-only-not-required.md`,
`docs/adr/0003-first-bundle-config-option-parameter-wiring.md`, and
`docs/adr/0004-impersonation-detection-diverges-from-auditlogger.md`.

Code: `src/EventListener/SentryUserContextListener.php`

## Workflow

- Registered in `config/services.php` as `framework.sentry_user_context_listener`, tagged
  `kernel.event_listener` on `kernel.request` / `onKernelRequest` — no explicit priority, so it runs after
  Symfony's own firewall listener (priority `8` on `kernel.request`) has already populated the security token.
- Three independent no-op guards before doing anything: `$enabled` is `false` (config, explicitly opted out —
  defaults to `true`), `$hub` is `null` (Sentry not installed/configured), or the request isn't the main
  request. `$enabled` is bound from the container parameter
  `sumo_coders_framework_core.sentry_user_context.enabled`, itself sourced from `Configuration.php`'s
  `sentry_user_context.enabled` node (default `true`) — the bundle's first real config option.
- `?HubInterface $hub` autowires to `null` automatically when `sentry/sentry-symfony` isn't installed by the
  consuming project — this is the entire mechanism behind Sentry being optional; there's no explicit
  `class_exists()` check anywhere.
- With no user authenticated (`Security::getUser()` isn't a `UserInterface`), also no-ops.
- Otherwise calls `$hub->configureScope()` and sets `Scope::setUser(new UserDataBag(id: $user->getUserIdentifier()))`
  — only the generic Symfony user identifier, deliberately never email or any app-specific field (see ADR-0001).
- Impersonation: a private `getImpersonatorId()` checks `isGranted('IS_IMPERSONATOR')` and
  `$token instanceof SwitchUserToken`, then reads `$token->getOriginalToken()->getUser()`. If present, sets
  `Scope::setContext('impersonation', ['impersonator_id' => ...])` alongside the impersonated user.

## Where it is used

- Purely a side effect on the Sentry scope for whatever error-reporting happens later in the request — nothing
  else in the bundle calls this listener or depends on its output directly.

## Edge cases

- **Two impersonation idioms coexist in this bundle.** `src/Logger/AuditLogger.php::getImpersonatingUser()`
  detects impersonation via `isGranted('ROLE_PREVIOUS_ADMIN')` with no `instanceof SwitchUserToken` guard — a
  different check than this listener's `IS_IMPERSONATOR` + instanceof guard. Both are correct; this is a
  deliberate, accepted inconsistency (see ADR-0004), not a bug to reconcile as a side effect of an unrelated
  change.
- No numeric id, email, or other profile data ever reaches Sentry from this listener, by design — don't add
  fields here to "enrich" the data without revisiting ADR-0001 first, since that decision was specifically about
  avoiding PII and app-entity coupling in a bundle shared across projects.
