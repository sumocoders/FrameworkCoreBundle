# sentry/sentry-symfony stays out of the bundle's production dependencies

`composer.json` adds `sentry/sentry-symfony` under `suggest`, never under `require`. `SentryUserContextListener`
autowires a nullable `?Sentry\State\HubInterface $hub = null` and no-ops when it's null, mirroring the pattern
the vvkp reference implementation itself already relies on (`HubInterface` there is only autowirable because
`SentryBundle` is registered in `prod`, not `dev`/`test`).

The alternative — adding `sentry/sentry-symfony` to `require` — was rejected because `FrameworkCoreBundle` is
installed into every SumoCoders project via `application-skeleton`, and not all of them use Sentry. Forcing the
dependency (and its own transitive requirements) onto projects that never touch Sentry isn't a cost this shared
bundle should impose; `suggest` plus nullable autowiring gets the same feature without that cost, at the price of
the listener needing an explicit null-check instead of a hard type dependency.

One addition beyond the original scope: `sentry/sentry-symfony` (or the narrower `sentry/sentry`) was also added
to `require-dev`, purely so this bundle's own test suite and static analysis (phpstan, mago) have the real
`HubInterface`/`Scope`/`UserDataBag` classes to check against, instead of hand-rolled fakes or suppressed
analysis. That's a dev-time-only carve-out — it doesn't change what `composer require sumocoders/framework-core-
bundle` pulls into a consuming project's own `vendor/`, since `require-dev` is never installed for dependents.
