# Sentry user context uses only the generic user identifier

`SentryUserContextListener` attaches `Symfony\Component\Security\Core\User\UserInterface::getUserIdentifier()` as
the Sentry user id — nothing else. No email, no numeric id, no separate profile fields, and no bundle-defined
marker interface for consuming apps' `User` entities to implement.

We considered two alternatives: a marker interface (e.g. `getSentryUserId()`/`getSentryEmail()`) that each
project's `User` entity would implement, and reflection/duck-typing to opportunistically pull `getId()`/
`getEmail()` off whatever `getUser()` returns. Both were rejected. `FrameworkCoreBundle` is shared across many
projects with their own `User` entity shapes — the bundle has no business knowing about any of them, and a
marker interface would still require every consuming app to add it. Email is also PII with GDPR weight; sending
it to Sentry is a per-project call, not a default a shared bundle should make for everyone. `getUserIdentifier()`
is the one thing every `UserInterface` implementation already guarantees, so it needed no new contract and no
opt-in from consuming apps to start working.

The reference implementation this was ported from (vvkp's `SentryUserContextListener`) does send id + email,
because it's app-specific code that already knows its own `User` entity — that's not a mismatch, it's the
difference between an app-level listener and a bundle-level one.
