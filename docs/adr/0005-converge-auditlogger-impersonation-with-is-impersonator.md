# Converge AuditLogger's impersonation check onto IS_IMPERSONATOR, superseding ADR-0004

`AuditLogger::getImpersonatingUser()` detected impersonation via `isGranted('ROLE_PREVIOUS_ADMIN')`, with no
`instanceof SwitchUserToken` guard before calling `getOriginalToken()->getUser()` — the unchecked call needed a
`phpcs:ignore`, an `@mago-expect`, and a `@phpstan-ignore` suppression to pass static analysis. It now uses
`isGranted('IS_IMPERSONATOR')`, then guards the token with `instanceof SwitchUserToken`, then guards the
resulting user with `instanceof UserInterface`, identical in shape to
`SentryUserContextListener::getImpersonatorId()`. The only difference between the two methods is their return
type: `SentryUserContextListener` returns the identifier string, `AuditLogger` returns the `UserInterface` object
itself, matching its pre-existing signature.

ADR-0004 considered this exact convergence and rejected it, but only because it would have meant touching
`AuditLogger`'s existing, working behavior as an incidental side effect of the unrelated Sentry user context
feature. It explicitly called converging the two idioms "a reasonable future cleanup." This change is that
cleanup, done as its own deliberate, directly-requested change rather than a side effect of something else — the
ask was specifically to eliminate the inconsistency between the two methods and the static-analysis suppression
comments `AuditLogger`'s unchecked call required.

No shared helper or service was extracted. Each class keeps its own private method
(`AuditLogger::getImpersonatingUser()`, `SentryUserContextListener::getImpersonatorId()`) — they are now
structurally identical rather than sharing code. Net effect: the bundle has one impersonation-detection idiom
instead of two, and `AuditLogger` no longer needs any static-analysis suppression comments for this check.
