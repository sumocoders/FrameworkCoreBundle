# Sentry impersonation detection uses IS_IMPERSONATOR, diverging from AuditLogger

`SentryUserContextListener` detects impersonation via `$security->isGranted('IS_IMPERSONATOR')` combined with
`$token instanceof Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken`, then reads
`$token->getOriginalToken()->getUser()`. This mirrors the vvkp reference implementation exactly.

This bundle already had one impersonation check before this feature —
`AuditLogger::getImpersonatingUser()` — which uses `isGranted('ROLE_PREVIOUS_ADMIN')` instead, with no
`instanceof SwitchUserToken` guard before calling `getOriginalToken()`. We considered aligning the new listener
with that existing idiom for internal consistency, and rejected it: the brief this feature was built from
specifically called for mirroring vvkp's implementation, `IS_IMPERSONATOR` is Symfony's own documented attribute
for this check (`ROLE_PREVIOUS_ADMIN` is the underlying role it's built from, present on the token as an
implementation detail rather than the intended check surface), and the explicit `instanceof` guard avoids needing
a static-analysis suppression the way `AuditLogger`'s unchecked call does.

Net effect: the bundle now has two different impersonation-detection idioms doing the same thing. That's a known,
accepted inconsistency, not an oversight — converging them onto one shared helper is a reasonable future cleanup,
but out of scope here since it would mean touching `AuditLogger`'s existing, working behavior as a side effect of
an unrelated feature.
