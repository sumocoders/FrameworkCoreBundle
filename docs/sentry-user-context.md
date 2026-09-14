# Sentry user context

Attaches the current authenticated user's identifier to the Sentry scope, so errors reported to Sentry are tied to a
user. When the request is impersonated (Symfony "switch user"), the impersonator's identifier is attached as
additional context.

## Prerequisites

- A Sentry hub must be registered in the container. In practice this means the consuming project has installed and
  configured [`sentry/sentry-symfony`](https://github.com/getsentry/sentry-symfony) (DSN, `config/bundles.php`, etc.)
  — that setup is entirely the consuming project's own concern, not this bundle's.
- The feature is on by default; set `sentry_user_context.enabled` to `false` via bundle config to turn it off
  (see below).

If Sentry is not installed at all, `SentryUserContextListener` autowires a `null` hub and no-ops on every request —
the bundle installs and works fine in projects that never touch Sentry.

## Options

| Parameter                       | Type   | Default | Description                                                    |
|----------------------------------|--------|---------|------------------------------------------------------------------|
| `sentry_user_context.enabled`   | `bool` | `true`  | Attaches the authenticated user's identity to Sentry's scope |

## Configuration

Enabled by default — no configuration needed to turn it on. To disable it:

```yaml
# config/packages/sumo_coders_framework_core.yaml
sumo_coders_framework_core:
    sentry_user_context:
        enabled: false
```

## What gets attached

- **User id**: `Symfony\Component\Security\Core\User\UserInterface::getUserIdentifier()` of the currently
  authenticated user, set as the Sentry scope's user (`Sentry\UserDataBag`). No email address or any other profile
  field is ever sent — only the identifier.
- **Impersonation**: when the request is impersonated (`IS_IMPERSONATOR` + a `SwitchUserToken`), the original
  (impersonating) user's identifier is attached as a separate `impersonation` context: `['impersonator_id' => ...]`.

Nothing is attached when there is no authenticated user, when the feature is disabled, or when no Sentry hub is
registered.

## Full example

No config needed — with `sentry/sentry-symfony` installed and configured, an error thrown while `jane.doe` is
logged in shows up in Sentry tagged with:

```
user.id: jane.doe
```

If an admin is impersonating `jane.doe` at the time, the event additionally carries:

```
impersonation.impersonator_id: admin
```

## Internals

- **Listener priority**: `SentryUserContextListener` is registered on `kernel.request` with no explicit priority.
  Symfony's own firewall listener runs on that same event at priority `8`, so it always runs first — by the time
  this listener runs, the security token (and thus the authenticated user, if any) is already available on
  `Security::getUser()`.
- **Deliberate inconsistency, do not "fix" it**: this listener detects impersonation via
  `isGranted('IS_IMPERSONATOR')` combined with checking that the token is a `SwitchUserToken` instance. Elsewhere
  in the bundle, `AuditLogger::getImpersonatingUser()` detects impersonation via `isGranted('ROLE_PREVIOUS_ADMIN')`
  with no `instanceof` check at all. Both are correct for their own use case — this divergence is an accepted,
  documented decision (see ADR-0004 below), not something to reconcile as a side effect of an unrelated change.

Decision records for the design choices behind this feature:

- [`docs/adr/0001-sentry-user-identity-generic-identifier-only.md`](adr/0001-sentry-user-identity-generic-identifier-only.md) —
  why only the generic `getUserIdentifier()` is sent, never email or other profile data.
- [`docs/adr/0002-sentry-symfony-suggest-only-not-required.md`](adr/0002-sentry-symfony-suggest-only-not-required.md) —
  why `sentry/sentry-symfony` is a `suggest`, not a hard dependency, and how the nullable `?HubInterface` autowiring
  makes that work.
- [`docs/adr/0003-first-bundle-config-option-parameter-wiring.md`](adr/0003-first-bundle-config-option-parameter-wiring.md) —
  `sentry_user_context.enabled` is the bundle's first real config option, and why it's wired via a container
  parameter bound to a constructor flag rather than conditional service registration.
- [`docs/adr/0004-impersonation-detection-diverges-from-auditlogger.md`](adr/0004-impersonation-detection-diverges-from-auditlogger.md) —
  why this listener's impersonation check intentionally diverges from `AuditLogger`'s.

## Troubleshooting

- **Nothing shows up in Sentry**: confirm `sentry_user_context.enabled` hasn't been explicitly set to `false` —
  the feature is on by default. Also check `sentry/sentry-symfony` (or another package providing a
  `Sentry\State\HubInterface` service) is installed and configured; `SentryUserContextListener` no-ops entirely
  when no hub is registered.
- **Nothing is attached on an anonymous request**: expected — the listener only acts when there is an authenticated
  user (`Security::getUser()` returns a `UserInterface`).
- **Impersonator id is missing while impersonating**: the listener detects impersonation via
  `isGranted('IS_IMPERSONATOR')` combined with the current token being a `SwitchUserToken`; verify Symfony's "switch
  user" feature is configured and the original (impersonating) token actually implements `UserInterface`.
- **Email address expected in Sentry but not present**: by design — only the user identifier is ever sent, no email
  or other profile data.
